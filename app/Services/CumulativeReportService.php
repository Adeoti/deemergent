<?php

namespace App\Services;

use App\Models\ResultRoot;
use App\Models\ResultUpload;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\HOSRemark;
use App\Models\TeacherRemark;

class CumulativeReportService
{
    /**
     * The full mark for one term/subject sitting, used to compute the
     * percentage shown next to each term column and the annual summary
     * (e.g. 100% per term sat, 300% for a full 3-term annual summary).
     *
     * This assumes every term's total is graded out of 100 — the standard
     * convention regardless of how individual CA/Exam columns are split.
     * Adjust this single constant if your school uses a different full mark.
     */
    public const FULL_MARK_PER_TERM = 100;

    /**
     * Build the full cumulative dataset for a given academic session + class.
     *
     * Returns an array shaped like:
     * [
     *     'session' => '2025/2026',
     *     'class' => SchoolClass,
     *     'terms' => ['1st Term', '2nd Term', '3rd Term'], // terms that actually have a ResultRoot for this session+class
     *     'roots' => [ '1st Term' => ResultRoot|null, ... ],
     *     'students' => [
     *         studentId => [
     *             'info' => User,
     *             'subjects' => [
     *                 subjectName => [
     *                     'per_term' => ['1st Term' => 70, '2nd Term' => 65, '3rd Term' => null, ...],
     *                     'terms_sat' => 2,
     *                     'annual_summary' => 135,
     *                     'annual_average' => 67.5,
     *                 ],
     *             ],
     *             'overall' => [
     *                 'per_term' => ['1st Term' => 350, ...], // sum of all subject totals for that term
     *                 'annual_summary' => 700,
     *                 'annual_average' => 70.0,
     *             ],
     *         ],
     *     ],
     *     'teacherRemarks' => Collection keyed by student_id (from the LAST available term's root),
     *     'hosRemarks' => Collection keyed by student_id (from the LAST available term's root),
     * ]
     */
    public function build(string $academicSession, int $classId): array
    {
        $allTerms = array_keys(ResultRoot::termOptions());

        $class = SchoolClass::find($classId);

        // Find the ResultRoot for each term in this session/class.
        // A "root" here represents one term's sitting. We match by academic_session + term,
        // and we only consider roots that actually have uploads tied to this class.
        $roots = [];
        foreach ($allTerms as $term) {
            $root = ResultRoot::where('academic_session', $academicSession)
                ->where('term', $term)
                ->whereHas('resultUploads', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                })
                ->first();

            $roots[$term] = $root;
        }

        $termsPresent = array_keys(array_filter($roots));

        $students = [];
        $subjectOrder = []; // preserve a stable subject display order across terms

        foreach ($termsPresent as $term) {
            $root = $roots[$term];

            $uploads = ResultUpload::where('result_root_id', $root->id)
                ->where('class_id', $classId)
                ->get();

            foreach ($uploads as $upload) {
                $subject = Subject::find($upload->subject_id);
                $subjectName = $subject->name ?? 'No Subject';

                if (!in_array($subjectName, $subjectOrder)) {
                    $subjectOrder[] = $subjectName;
                }

                $cardItems = is_array($upload->card_items)
                    ? $upload->card_items
                    : json_decode($upload->card_items, true);

                if (!is_array($cardItems)) {
                    continue;
                }

                foreach ($cardItems as $studentId => $result) {
                    $student = User::find($studentId);
                    if (!$student) {
                        continue;
                    }

                    if (!isset($students[$studentId])) {
                        $students[$studentId] = [
                            'info' => $student,
                            'subjects' => [],
                        ];
                    }

                    if (!isset($students[$studentId]['subjects'][$subjectName])) {
                        $students[$studentId]['subjects'][$subjectName] = [
                            'per_term' => array_fill_keys($allTerms, null),
                        ];
                    }

                    $total = $result['total'] ?? null;
                    $students[$studentId]['subjects'][$subjectName]['per_term'][$term] = $total;
                }
            }
        }

        // Compute annual summary/average per subject, and overall per-term + annual totals per student.
        foreach ($students as $studentId => &$studentData) {
            $overallPerTerm = array_fill_keys($allTerms, 0);
            $overallTermsSat = array_fill_keys($allTerms, 0);

            foreach ($studentData['subjects'] as $subjectName => &$subjectData) {
                $sum = 0;
                $termsSat = 0;

                foreach ($allTerms as $term) {
                    $value = $subjectData['per_term'][$term];
                    if ($value !== null && $value !== '' && $value !== 'N/A') {
                        $sum += (float) $value;
                        $termsSat++;
                        $overallPerTerm[$term] += (float) $value;
                        $overallTermsSat[$term] = 1; // mark term as having at least one subject (used for overall avg)
                    }
                }

                $subjectData['terms_sat'] = $termsSat;
                $subjectData['annual_summary'] = $termsSat > 0 ? $sum : null;
                $subjectData['annual_average'] = $termsSat > 0 ? round($sum / $termsSat, 2) : null;

                // Percentage shown under each term column header is always
                // "out of FULL_MARK_PER_TERM" for any term that was sat (100%),
                // and the annual summary percentage scales with how many
                // terms were sat (e.g. 200% for 2 terms, 300% for 3 terms).
                $subjectData['per_term_percent'] = [];
                foreach ($allTerms as $term) {
                    $subjectData['per_term_percent'][$term] = $subjectData['per_term'][$term] !== null
                        ? self::FULL_MARK_PER_TERM
                        : null;
                }
                $subjectData['annual_summary_percent'] = $termsSat > 0
                    ? $termsSat * self::FULL_MARK_PER_TERM
                    : null;
            }

            // Overall (all-subjects) summary per term and annual
            $annualOverallSum = 0;
            $annualOverallTermsSat = 0;
            $overallPerTermPercent = [];
            foreach ($allTerms as $term) {
                if ($overallTermsSat[$term] > 0) {
                    $annualOverallSum += $overallPerTerm[$term];
                    $annualOverallTermsSat++;
                    $overallPerTermPercent[$term] = self::FULL_MARK_PER_TERM;
                } else {
                    $overallPerTerm[$term] = null;
                    $overallPerTermPercent[$term] = null;
                }
            }

            $studentData['overall'] = [
                'per_term' => $overallPerTerm,
                'per_term_percent' => $overallPerTermPercent,
                'annual_summary' => $annualOverallTermsSat > 0 ? $annualOverallSum : null,
                'annual_summary_percent' => $annualOverallTermsSat > 0 ? $annualOverallTermsSat * self::FULL_MARK_PER_TERM : null,
                'annual_average' => $annualOverallTermsSat > 0 ? round($annualOverallSum / $annualOverallTermsSat, 2) : null,
            ];
        }
        unset($studentData, $subjectData);

        // Remarks: pull from the most recent term that has a root, falling back backwards.
        $latestRoot = null;
        foreach (array_reverse($allTerms) as $term) {
            if ($roots[$term]) {
                $latestRoot = $roots[$term];
                break;
            }
        }

        $teacherRemarks = $latestRoot
            ? TeacherRemark::where('result_root_id', $latestRoot->id)->get()->keyBy('student_id')
            : collect();

        $hosRemarks = $latestRoot
            ? HOSRemark::where('result_root_id', $latestRoot->id)->get()->keyBy('student_id')
            : collect();

        // Rank students by overall annual summary (highest first) for position purposes, if needed later.
        uasort($students, function ($a, $b) {
            return ($b['overall']['annual_summary'] ?? 0) <=> ($a['overall']['annual_summary'] ?? 0);
        });

        return [
            'session' => $academicSession,
            'class' => $class,
            'all_terms' => $allTerms,
            'terms_present' => $termsPresent,
            'roots' => $roots,
            'subject_order' => $subjectOrder,
            'students' => $students,
            'teacherRemarks' => $teacherRemarks,
            'hosRemarks' => $hosRemarks,
            'latestRoot' => $latestRoot,
        ];
    }

    /**
     * Convenience helper to build the dataset filtered down to a single student.
     */
    public function buildForStudent(string $academicSession, int $classId, int $studentId): array
    {
        $data = $this->build($academicSession, $classId);

        if (isset($data['students'][$studentId])) {
            $data['students'] = [$studentId => $data['students'][$studentId]];
        } else {
            $data['students'] = [];
        }

        return $data;
    }
}
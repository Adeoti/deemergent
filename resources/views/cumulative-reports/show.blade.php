@php
    use App\Models\Attendance;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cumulative Report - {{ $class->name ?? 'Class' }} ({{ $session }})</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        div.key-to-grades {
            background-color: #f4fa9c;
            text-align: center;
            padding: 10px;
        }

        table {
            margin-top: 20px;
        }

        .table-head {
            border-bottom: 2px solid #056b05;
        }

        .table-head th {
            font-weight: 700;
            color: #056b05;
        }

        .percent-row td,
        .percent-row th {
            font-size: 0.75rem;
            font-weight: 500;
            color: #555;
            font-style: italic;
            border-top: none;
            padding-top: 0 !important;
        }

        .annual-col {
            font-weight: 700;
        }

        .student-report-card {
            page-break-before: always;
            page-break-inside: auto;
            margin: 0;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .student-report-card:first-child {
            page-break-before: auto;
        }

        .print-remove {
            display: none !important;
        }

        @media print {
            .print-remove {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-4">

    <div class="print-remove" style="background: #003333; color:#fff; text-align:center; padding:10px 0px; margin-bottom:20px; border-radius: 8px;">
        <a href="{{ route('cumulative-reports.select') }}" style="color:#fff; text-decoration:none; margin-right:20px;">&larr; Back to selection</a>
        <button onclick="printAllCumulativeCards()"
            style="border-radius:10px; border:1px solid #fff; padding:5px 14px; background:transparent; color:white; cursor:pointer;">
            Print / Save as PDF
        </button>
        <span id="print-loading" style="display:none; margin-left:10px;">Preparing print...</span>
    </div>

    @if (empty($students))
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-600">
            No cumulative results found for <strong>{{ $class->name ?? 'this class' }}</strong> in the
            <strong>{{ $session }}</strong> session.
        </div>
    @else
        @php
            $school_logo = $schoolDetails['school_logo'] ?? null;
            $principal_signature = $schoolDetails['principal_signature'] ?? null;
            $numberInClass = count($students);
        @endphp

        @foreach ($students as $studentId => $studentData)
            <div class="student-report-card border p-6 mb-6 rounded-lg shadow-lg" style="margin-top:15px; margin-bottom:15px;">

                {{-- Header --}}
                <div class="mb-4 flex justify-between border p-2">
                    <div class="school_logo">
                        @if ($school_logo)
                            <img src="{{ Storage::url($school_logo) }}" alt="Logo" style="height: 70px; border-radius: 10%;">
                        @endif
                    </div>
                    <div class="text-center">
                        <h2 class="font-bold" style="font-size: 2.2rem;">{{ $schoolDetails['school_name'] ?? '' }}</h2>
                        <p><span style="font-weight: 600; color:darkmagenta;">
                                Annual Cumulative Report &mdash; {{ $session }}
                            </span></p>
                    </div>
                    <div class="student_passport">
                        @if (!empty($studentData['info']->passport))
                            <img src="{{ Storage::url($studentData['info']->passport) }}" alt="Passport" style="height: 70px; border-radius: 10%;">
                        @endif
                    </div>
                </div>

                {{-- Student Info --}}
                <div class="mb-4 flex justify-between border p-2">
                    <div>
                        <h2 class="text-xl font-bold">{{ $studentData['info']->name }}</h2>
                        <p>Email: {{ $studentData['info']->email }}</p>
                        <p><span class="font-semibold">Class:</span> {{ $class->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p><span class="font-semibold">Admission Number:</span>
                            {{ $studentData['info']->student->roll_number ?? 'N/A' }}</p>
                        <p><span class="font-semibold">Parent:</span>
                            {{ $studentData['info']->student->guardian_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p><span class="font-semibold">Number In Class:</span> {{ $numberInClass }}</p>
                        <p><span class="font-semibold">Terms Covered:</span> {{ implode(', ', $termsPresent) }}</p>
                    </div>
                </div>

                {{-- Cumulative Subjects Table --}}
                <table class="w-full border-collapse border border-gray-300 text-left">
                    <thead>
                        <tr class="table-head">
                            <th class="border px-2 py-1" rowspan="2">SUBJECTS</th>
                            @foreach ($allTerms as $term)
                                <th class="border px-2 py-1 text-center">{{ $term }}</th>
                            @endforeach
                            <th class="border px-2 py-1 text-center annual-col">Annual Summary</th>
                            <th class="border px-2 py-1 text-center annual-col">Annual Average</th>
                        </tr>
                        <tr class="percent-row">
                            @foreach ($allTerms as $term)
                                <th class="border px-2 py-1 text-center">100%</th>
                            @endforeach
                            <th class="border px-2 py-1 text-center">{{ count($allTerms) * 100 }}%</th>
                            <th class="border px-2 py-1 text-center">100%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjectOrder as $subjectName)
                            @php
                                $subjectData = $studentData['subjects'][$subjectName] ?? null;
                            @endphp
                            <tr>
                                <td class="border px-2 py-1 font-medium">{{ $subjectName }}</td>
                                @foreach ($allTerms as $term)
                                    <td class="border px-2 py-1 text-center">
                                        @if (($subjectData['per_term'][$term] ?? null) !== null)
                                            {{ $subjectData['per_term'][$term] }}
                                            <span class="text-xs text-gray-500">({{ $subjectData['per_term_percent'][$term] }}%)</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                <td class="border px-2 py-1 text-center annual-col">
                                    @if (($subjectData['annual_summary'] ?? null) !== null)
                                        {{ $subjectData['annual_summary'] }}
                                        <span class="text-xs text-gray-500">({{ $subjectData['annual_summary_percent'] }}%)</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="border px-2 py-1 text-center annual-col">
                                    {{ $subjectData['annual_average'] ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-head">
                            <td class="border px-2 py-1">OVERALL</td>
                            @foreach ($allTerms as $term)
                                <td class="border px-2 py-1 text-center">
                                    @if (($studentData['overall']['per_term'][$term] ?? null) !== null)
                                        {{ $studentData['overall']['per_term'][$term] }}
                                        <span class="text-xs" style="color:#777;">({{ $studentData['overall']['per_term_percent'][$term] }}%)</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endforeach
                            <td class="border px-2 py-1 text-center annual-col" style="color:#056b05;">
                                @if (($studentData['overall']['annual_summary'] ?? null) !== null)
                                    {{ $studentData['overall']['annual_summary'] }}
                                    <span class="text-xs" style="color:#777;">({{ $studentData['overall']['annual_summary_percent'] }}%)</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border px-2 py-1 text-center annual-col" style="color:#056b05;">
                                {{ $studentData['overall']['annual_average'] ?? '-' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Remarks --}}
                {{-- <div class="teacher_comment">
                    <br>
                    <table class="w-full">
                        <thead>
                            <tr class="table-head">
                                <th style="text-align:center;" colspan="2">Remarks/Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Class Teacher's Remark</td>
                                <td class="border px-2 py-1">
                                    {{ $teacherRemarks[$studentId]->remark ?? 'No remark added yet by class teacher' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Proprietor's Remark</td>
                                <td class="border px-2 py-1">
                                    {{ $hosRemarks[$studentId]->remark ?? 'No remark added yet' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table style="width: 100%; margin-top:30px;">
                        <tr>
                            <td style="padding:15px;">
                                {{ $latestRoot->teacher->name ?? '' }}
                                <br>
                                <b><cite>Class Teacher</cite></b>
                            </td>
                            <td style="padding:15px;">
                                @if ($principal_signature)
                                    <img src="{{ Storage::url($principal_signature) }}" alt="signature" style="height: 50px;">
                                @endif
                                {{ $schoolDetails['principal_name'] ?? '' }}
                                <br>
                                <b><cite>Proprietor</cite></b>
                            </td>
                        </tr>
                    </table>
                    <br><hr><br>
                </div> --}}
            </div>
        @endforeach
    @endif

    <script>
        async function printAllCumulativeCards() {
            const loadingSpan = document.getElementById('print-loading');
            if (loadingSpan) loadingSpan.style.display = 'inline';

            try {
                const printWindow = window.open('', '_blank');

                const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
                let stylesHTML = '';
                styles.forEach(el => stylesHTML += el.outerHTML);

                const printCSS = `
                    <style>
                        .student-report-card {
                            page-break-before: always;
                            margin: 0;
                            padding: 1.5rem;
                            border: 1px solid #ddd;
                            border-radius: 0.5rem;
                            background: white;
                        }
                        .student-report-card:first-child { page-break-before: auto; }
                        .print-remove, button { display: none !important; }
                        img { max-width: 100%; height: auto; }
                    </style>
                `;

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Cumulative Report - {{ $class->name ?? '' }} ({{ $session }})</title>
                        ${stylesHTML}
                        ${printCSS}
                    </head>
                    <body style="background: white; padding: 0; margin: 0;">
                `);

                const cards = document.querySelectorAll('.student-report-card');
                if (cards.length === 0) {
                    alert('No student cards found.');
                    printWindow.close();
                    return;
                }

                cards.forEach((card) => {
                    const clone = card.cloneNode(true);
                    clone.querySelectorAll('.print-remove, button').forEach(el => el.remove());
                    printWindow.document.write(clone.outerHTML);
                });

                printWindow.document.write('</body></html>');
                printWindow.document.close();

                await new Promise(resolve => {
                    printWindow.onload = resolve;
                    setTimeout(resolve, 2000);
                });

                printWindow.focus();
                printWindow.print();
            } catch (error) {
                console.error('Print preparation failed:', error);
                alert('Failed to prepare print. Check console for details.');
            } finally {
                if (loadingSpan) loadingSpan.style.display = 'none';
            }
        }
    </script>

</body>

</html>
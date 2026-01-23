<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $broadsheet->name }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .school-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .broadsheet-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .total-column {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #000;
        }
        
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        
        .signature-box {
            text-align: center;
            width: 30%;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 30px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="header">
        @if ($schoolDetails['school_logo'] ?? false)
            <img src="{{ storage_path('app/public/' . str_replace('public/', '', $schoolDetails['school_logo'])) }}" 
                 alt="School Logo" 
                 style="height: 60px; margin-bottom: 10px;">
        @endif
        
        <div class="school-name">{{ $schoolDetails['school_name'] ?? 'SCHOOL NAME' }}</div>
        <div class="broadsheet-title">{{ $broadsheet->name }}</div>
        
        <div class="info-row">
            <div>Class: {{ $classInfo->name ?? 'N/A' }}</div>
            <div>Term: {{ $broadsheet->term ?? 'N/A' }}</div>
            <div>Date: {{ date('F j, Y') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>S/No</th>
                <th>Name</th>
                <th>Roll No.</th>
                
                @foreach ($broadsheetData['subjects'] ?? [] as $subjectName)
                    <th>{{ $subjectName }}</th>
                @endforeach
                
                <th class="total-column">TOTAL</th>
                <th class="total-column">AVERAGE</th>
                <th class="total-column">POSITION</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($broadsheetData['students'] ?? [] as $student)
                <tr>
                    <td>{{ $student['sno'] }}</td>
                    <td style="text-align: left;">{{ $student['name'] }}</td>
                    <td>{{ $student['roll_number'] }}</td>
                    
                    @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                        <td>{{ $student['subjects'][$subjectId]['score'] ?? 0 }}</td>
                    @endforeach
                    
                    <td class="total-column">{{ $student['total'] }}</td>
                    <td class="total-column">{{ $student['average'] ?? 'N/A' }}</td>
                    <td class="total-column">{{ $student['position'] }}</td>
                </tr>
            @endforeach
            
            <!-- Summary Row -->
            <tr style="background-color: #333; color: white; font-weight: bold;">
                <td colspan="3" style="text-align: left;">CLASS SUMMARY</td>
                
                @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                    <td>
                        @php
                            $subjectScores = array_column(array_column($broadsheetData['students'] ?? [], 'subjects'), $subjectId);
                            $subjectTotals = array_column($subjectScores, 'score');
                            $subjectAverage = count($subjectTotals) > 0 ? round(array_sum($subjectTotals) / count($subjectTotals), 2) : 0;
                        @endphp
                        {{ $subjectAverage }}
                    </td>
                @endforeach
                
                <td>
                    @php
                        $classTotal = array_sum(array_column($broadsheetData['students'] ?? [], 'total'));
                    @endphp
                    {{ $classTotal }}
                </td>
                <td>
                    @php
                        $classAverage = count($broadsheetData['students'] ?? []) > 0 
                            ? round($classTotal / count($broadsheetData['students']), 2) 
                            : 0;
                    @endphp
                    {{ $classAverage }}
                </td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <h4>Performance Statistics:</h4>
        <p>Total Students: {{ $broadsheetData['total_students'] ?? 0 }}</p>
        <p>Class Average: {{ $classAverage ?? 0 }}%</p>
        <p>Highest Score: {{ max(array_column($broadsheetData['students'] ?? [], 'total')) ?? 0 }}</p>
        <p>Lowest Score: {{ min(array_column($broadsheetData['students'] ?? [], 'total')) ?? 0 }}</p>
    </div>

    <div class="footer">
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Class Teacher</p>
                <p>{{ $broadsheet->creator->name ?? 'N/A' }}</p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Head of Section</p>
                <p>{{ $schoolDetails['principal_name'] ?? 'N/A' }}</p>
            </div>
            
            <div class="signature-box">
                <div class="signature-line"></div>
                <p>Principal</p>
                <p>{{ $schoolDetails['principal_name'] ?? 'N/A' }}</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px; font-size: 10px; color: #666;">
            <p>Generated by Paramount Edusoft • {{ date('F j, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
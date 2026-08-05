<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .letterhead { text-align: center; margin-bottom: 4px; }
        .letterhead .eyebrow { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; }
        .letterhead h1 { font-size: 20px; margin: 2px 0; color: #0B3D62; }
        .letterhead .tel { font-size: 11px; color: #6b7280; margin-bottom: 10px; }
        .logo { max-height: 55px; margin-bottom: 4px; }

        .meta-table { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; font-size: 12px; }

        table.results { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.results th { background: #0B3D62; color: #fff; padding: 6px; text-align: left; font-size: 10.5px; }
        table.results td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        table.results tr:nth-child(even) td { background: #f9fafb; }
        .text-end { text-align: center !important; }
        .uppercase { text-transform: uppercase !important; }

        .summary { background: #f9fafb; padding: 10px; border-radius: 6px; margin-bottom: 14px; display: flex; }
        .summary-item { margin-right: 24px; }
        .summary-label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .summary-value { font-size: 14px; font-weight: bold; color: #0B3D62; }

        .remarks-box { border: 1px solid #e5e7eb; padding: 10px; border-radius: 6px; margin-bottom: 10px; }
        .remarks-box .label { font-weight: bold; font-size: 11px; color: #0B3D62; margin-bottom: 4px; }
        .signature-line { margin-top: 16px; font-size: 11px; }

        .chart-wrap { margin-top: 16px; }
        .chart-title { font-size: 12px; font-weight: bold; color: #0B3D62; margin-bottom: 6px; }
        .chart-legend { font-size: 10px; color: #6b7280; margin-bottom: 6px; }
        .chart-legend .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
        .bars { width: 100%; }
        .bar-row { margin-bottom: 8px; }
        .bar-subject { font-size: 10px; color: #374151; margin-bottom: 2px; }
        .bar-track { background: #f3f4f6; border-radius: 3px; height: 10px; position: relative; margin-bottom: 2px; }
        .bar-fill-student { background: #0B3D62; height: 10px; border-radius: 3px; }
        .bar-fill-class { background: #9ca3af; height: 10px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="letterhead">
    @if($logoPath)
        <img src="{{ $logoPath }}" class="logo"><br>
    @endif
    <div class="eyebrow">Student End of Term Report Card</div>
    <h1>{{ $schoolName }}</h1>
    @if($schoolPhone ?? null)
        <div class="tel">Tel: {{ $schoolPhone }}</div>
    @endif
</div>

<table class="meta-table">
    <tr>
        <td style="width: 65% !important;"><strong>Student:</strong> {{ strtoupper($enrollment->student->full_name) ?? '-' }}</td>
        <td><strong>Admission No.:</strong> {{ $enrollment->student->userID ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Class:</strong> {{ $enrollment->stream->full_name ?? '-' }}</td>
        <td><strong>Term:</strong>Term {{ $overall->academicTerm->term_number }} {{ $overall->academicTerm->academic_year }} </td>
    </tr>
    <tr>
        <td><strong>Position in Class:</strong> {{ $overall->position_in_stream ?? '-' }} / {{ $overall->stream_size ?? '-' }}</td>
        <td><strong>Position in Grade:</strong> {{ $overall->position_in_grade ?? '-' }} / {{ $overall->grade_size ?? '-' }}</td>
    </tr>
</table>

<table class="results">
    <thead>
    <tr>
        <th>Subject</th>
        @foreach($rounds as $roundName)
            <th class="text-end">{{ strtoupper($roundName) }}</th>
        @endforeach
        <th class="text-end">Average</th>
        <th>Grade</th>
        <th class="text-end">Rank</th>
        <th>Teacher</th>
        <th>Comment</th>
    </tr>
    </thead>
    <tbody>
    @forelse($subjects as $subject)
        <tr>
            <td>{{ $subject['subject'] }}</td>
            @foreach($rounds as $roundName)
                <td class="text-end">{{ $subject['rounds'][$roundName] ?? '-' }}</td>
            @endforeach
            <td class="text-end">{{ $subject['average_score'] !== null ? $subject['average_score'] : ($subject['competency_level'] ?? '-') }}</td>
            <td>{{ $subject['letter_grade'] ?? '-' }}</td>
            <td class="text-end">{{ $subject['rank'] ? $subject['rank'] . ' / ' . $subject['out_of'] : '-' }}</td>
            <td>{{ $subject['teacher'] ?? '-' }}</td>
            <td style="width: 20% !important;">{{ $subject['remarks'] ?: '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="{{ count($rounds) + 5 }}">No approved subject results found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="summary">
    <div class="summary-item">
        <div class="summary-label">Total Marks</div>
        <div class="summary-value">{{ $overall->total_score ?? '-' }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Average Marks</div>
        <div class="summary-value">{{ $overall->average_score ?? '-' }}</div>
    </div>
</div>

<div class="remarks-box">
    <div class="label">Class Teacher's Comment</div>
    {{ $overall->class_teacher_remarks ?: '____________________________________________________________________________________________________________________________________' }}
</div>
{{--<div class="remarks-box">
    <div class="label">Headteacher's Comment</div>
    {{ $overall->principal_remarks ?: 'No remarks recorded.' }}
</div>--}}

<div class="signature-line">Parent's Signature: .....................................................</div>

@if(count($subjects) > 0)
    <div class="chart-wrap">
        <div class="chart-title">Performance vs Class Average</div>
        <div class="chart-legend">
            <span class="dot" style="background:#0B3D62;"></span> Student
            &nbsp;&nbsp;
            <span class="dot" style="background:#9ca3af;"></span> Class Average
        </div>
        <div class="bars">
            @foreach($subjects as $subject)
                @php
                    $studentVal = $subject['average_score'] !== null ? min(100, max(0, $subject['average_score'])) : 0;
                    $classVal = $subject['class_average'] !== null ? min(100, max(0, $subject['class_average'])) : 0;
                @endphp
                <div class="bar-row">
                    <div class="bar-subject">{{ $subject['subject'] }} ({{ $subject['average_score'] !== null ? number_format($subject['average_score'], 1) : '-' }} vs {{ $subject['class_average'] !== null ? number_format($subject['class_average'], 1) : '-' }})</div>
                    <div class="bar-track"><div class="bar-fill-student" style="width: {{ $studentVal }}%;"></div></div>
                    <div class="bar-track"><div class="bar-fill-class" style="width: {{ $classVal }}%;"></div></div>
                </div>
            @endforeach
        </div>
    </div>
@endif
</body>
</html>

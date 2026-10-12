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

        .summary { background: #f9fafb; padding: 10px; border-radius: 6px; margin-bottom: 14px; display: flex; }
        .summary-item { margin-right: 24px; }
        .summary-label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .summary-value { font-size: 14px; font-weight: bold; color: #0B3D62; }

        .chart-wrap { margin-top: 16px; }
        .chart-title { font-size: 12px; font-weight: bold; color: #0B3D62; margin-bottom: 6px; }
        .chart-legend { font-size: 10px; color: #6b7280; margin-bottom: 6px; }
        .chart-legend .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
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
    <div class="eyebrow">{{ $name }} Report Card</div>
    <h1>{{ $schoolName }}</h1>
    @if($schoolPhone ?? null)
        <div class="tel">Tel: {{ $schoolPhone }}</div>
    @endif
</div>

<table class="meta-table">
    <tr>
        <td style="width: 65% !important;"><strong>Student:</strong> {{ $row['enrollment']->student->full_name ?? '-' }}</td>
        <td><strong>Admission No.:</strong> {{ $row['enrollment']->student->userID ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Class:</strong> {{ $stream->full_name }}</td>
        <td><strong>Term:</strong> {{ $academicTerm->academic_year }} Term {{ $academicTerm->term_number }}</td>
    </tr>
    <tr>
        <td colspan="2"><strong>Position:</strong> {{ $row['position'] ? $row['position'] . ' / ' . $row['stream_size'] : '-' }}</td>
    </tr>
</table>

<table class="results">
    <thead>
    <tr><th>Subject</th><th class="">Score</th><th class="">Out of</th><th>Grade</th><th class="">Rank</th><th>Comments</th></tr>
    </thead>
    <tbody>
    @foreach($row['subjects'] as $subject)
        <tr>
            <td>{{ $subject['name'] }}</td>
            <td class="">{{ $subject['score'] == 0 ? 'Absent' : $subject['score'] }}</td>
            <td class="">{{ $subject['max_score'] ?? '-' }}</td>
            <td class="">{{ $subject['letter_grade'] ?? 'ABS' }}</td>
            <td class="">{{ $subject['rank'] ? $subject['rank'] . ' / ' . $subject['out_of'] : '-' }}</td>
            <td style="width: 20% !important;">{{ $subject['comments'] ?? '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="summary">
    <div class="summary-item">
        <div class="summary-label">Total</div>
        <div class="summary-value">{{ $row['total'] ?? '-' }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Average</div>
        <div class="summary-value">{{ $row['average'] ?? '-' }}</div>
    </div>
</div>

@if(count($row['subjects']) > 0)
    <div class="chart-wrap">
        <div class="chart-title">Performance vs Class Average</div>
        <div class="chart-legend">
            <span class="dot" style="background:#0B3D62;"></span> Student
            &nbsp;&nbsp;
            <span class="dot" style="background:#9ca3af;"></span> Class Average
        </div>
        @foreach($row['subjects'] as $subject)
            @php
                $max = $subject['max_score'] ?: 100;
                $studentPct = $subject['score'] !== null ? min(100, max(0, ($subject['score'] / $max) * 100)) : 0;
                $classPct = $subject['class_average'] !== null ? min(100, max(0, ($subject['class_average'] / $max) * 100)) : 0;
            @endphp
            <div class="bar-row">
                <div class="bar-subject">{{ $subject['name'] }} ({{ $subject['score'] ?? '-' }} vs {{ $subject['class_average'] !== null ? number_format($subject['class_average'], 1) : '-' }})</div>
                <div class="bar-track"><div class="bar-fill-student" style="width: {{ $studentPct }}%;"></div></div>
                <div class="bar-track"><div class="bar-fill-class" style="width: {{ $classPct }}%;"></div></div>
            </div>
        @endforeach
    </div>
@endif
</body>
</html>

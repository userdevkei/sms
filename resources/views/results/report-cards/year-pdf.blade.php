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
        .text-center { text-align: center; }

        .section-title { font-size: 12px; font-weight: bold; color: #0B3D62; margin: 4px 0 6px; }

        .summary { background: #f9fafb; padding: 10px; border-radius: 6px; margin-bottom: 14px; display: flex; }
        .summary-item { margin-right: 24px; }
        .summary-label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .summary-value { font-size: 14px; font-weight: bold; color: #0B3D62; }

        .chart-wrap { margin-top: 16px; }
        .chart-title { font-size: 12px; font-weight: bold; color: #0B3D62; margin-bottom: 6px; }
        .bar-row { margin-bottom: 8px; }
        .bar-label { font-size: 10px; color: #374151; margin-bottom: 2px; }
        .bar-track { background: #f3f4f6; border-radius: 3px; height: 12px; }
        .bar-fill { background: #0B3D62; height: 12px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="letterhead">
    @if($logoPath)
        <img src="{{ $logoPath }}" class="logo"><br>
    @endif
    <div class="eyebrow">Year Report Card</div>
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
        <td><strong>Academic Year:</strong> {{ $academicYear }}</td>
    </tr>
</table>

<div class="section-title">Subject Performance</div>
<table class="results">
    <thead>
    <tr>
        <th>Subject</th>
        <th class="text-end">Term 1</th>
        <th class="text-end">Term 2</th>
        <th class="text-end">Term 3</th>
        <th>Position</th>
        <th>Remarks</th>
    </tr>
    </thead>
    <tbody>
    @forelse($row['subjects'] as $subject)
        <tr>
            <td>{{ $subject['name'] }}</td>
            @foreach(['T1', 'T2', 'T3'] as $t)
                <td>
                    @if(isset($subject['terms'][$t]))
                        {{ $subject['terms'][$t]['average_score'] !== null
                            ? number_format($subject['terms'][$t]['average_score'], 1)
                            : ($subject['terms'][$t]['competency_level'] ?? '-') }}
                    @else
                        -
                    @endif
                </td>
            @endforeach
            <td>{{ $subject['position'] }}</td>
            <td>{{ $subject['remarks'] }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center" style="color:#6b7280;">No finalized subject results yet.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="section-title">Term Overview</div>
<table class="results">
    <thead>
    <tr><th>Term</th><th class="">Average</th><th class="">Position</th></tr>
    </thead>
    <tbody>
    @foreach(['T1', 'T2', 'T3'] as $t)
        <tr>
            <td>{{ $t == 'T1' ? 'Term 1' : ($t == 'T2' ? 'Term 2' : 'Term 3') }}</td>
            <td class="">{{ isset($row['terms'][$t]) ? number_format($row['terms'][$t]['average'], 1) : '-' }}</td>
            <td class="">
                {{ isset($row['terms'][$t]) ? ($row['terms'][$t]['position'] ?? '-') . ' / ' . ($row['terms'][$t]['stream_size'] ?? '-') : '-' }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="summary">
    <div class="summary-item">
        <div class="summary-label">Yearly Average</div>
        <div class="summary-value">{{ $row['yearly_average'] ?? '-' }}</div>
    </div>
    <div class="summary-item">
        <div class="summary-label">Yearly Position</div>
        <div class="summary-value">{{ $row['yearly_position'] ? $row['yearly_position'] . ' / ' . $row['yearly_size'] : '-' }}</div>
    </div>
</div>

@if(count($row['terms']) > 0)
    <div class="chart-wrap">
        <div class="chart-title">Term-by-Term Average</div>
        @foreach(['T1', 'T2', 'T3'] as $t)
            @if(isset($row['terms'][$t]))
                @php $pct = min(100, max(0, $row['terms'][$t]['average'])); @endphp
                <div class="bar-row">
                    <div class="bar-label">{{ $t }} ({{ number_format($row['terms'][$t]['average'], 1) }})</div>
                    <div class="bar-track"><div class="bar-fill" style="width: {{ $pct }}%;"></div></div>
                </div>
            @endif
        @endforeach
    </div>
@endif
</body>
</html>

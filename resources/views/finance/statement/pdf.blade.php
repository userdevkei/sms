<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .letterhead { text-align: center; margin-bottom: 4px; }
        .letterhead .eyebrow { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: #6b7280; }
        .letterhead h1 { font-size: 20px; margin: 2px 0; color: #0B3D62; }
        .logo { max-height: 55px; margin-bottom: 4px; }
        .meta-table { width: 100%; margin-bottom: 14px; border-collapse: collapse; }
        .meta-table td { padding: 3px 0; font-size: 12px; }
        table.ledger { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.ledger th { background: #0B3D62; color: #fff; padding: 6px; text-align: left; font-size: 10.5px; }
        table.ledger td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        .text-end { text-align: right; }
        .closing { background: #f9fafb; padding: 10px; border-radius: 6px; }
        .closing .label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .closing .value { font-size: 16px; font-weight: bold; color: #0B3D62; }
    </style>
</head>
<body>
<div class="letterhead">
    @if($logoPath)<img src="{{ $logoPath }}" class="logo"><br>@endif
    <div class="eyebrow">Student Statement</div>
    <h1>{{ $schoolName }}</h1>
</div>

<table class="meta-table">
    <tr>
        <td><strong>Student:</strong> {{ $user->full_name }}</td>
        <td><strong>Admission No.:</strong> {{ $user->userID }}</td>
    </tr>
    <tr><td colspan="2"><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</td></tr>
</table>

<table class="ledger">
    <thead><tr><th>Date</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
    <tbody>
    @foreach($lines as $line)
        <tr>
            <td>{{ $line['date']->format('d M Y') }}</td>
            <td>{{ $line['description'] }}</td>
            <td class="text-end">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '' }}</td>
            <td class="text-end">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '' }}</td>
            <td class="text-end">{{ number_format($line['balance'], 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="closing">
    <div class="label">Closing Balance</div>
    <div class="value">KES {{ number_format($closingBalance, 2) }}</div>
</div>
</body>
</html>

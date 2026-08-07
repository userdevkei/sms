<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7.5pt;
            color: #1e293b;
            background: #fff;
            padding: 28px 32px 60px;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #1e3a5f;
        }
        .header img {
            max-height: 60px;
            display: block;
            margin: 0 auto 8px;
        }
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.3px;
        }
        .doc-title {
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            margin-top: 4px;
        }
        .stmt-date {
            font-size: 6.5pt;
            color: #94a3b8;
            margin-top: 6px;
        }
        .stmt-date strong { color: #1e3a5f; }

        /* ── Student info block ── */
        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 3px 10px 3px 0; font-size: 7pt; vertical-align: top; }
        .info-label { font-weight: bold; color: #475569; width: 110px; white-space: nowrap; }
        .info-val   { color: #1e293b; }

        /* ── Summary cards ── */
        .summary-row { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 20px; }
        .summary-row td { width: 33.3%; }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 10px 14px;
            display: block;
        }
        .card-label {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            display: block;
            margin-bottom: 5px;
        }
        .card-value {
            font-size: 12pt;
            font-weight: bold;
            display: block;
            line-height: 1;
        }
        .card-unit {
            font-size: 7pt;
            font-weight: normal;
            color: #94a3b8;
            margin-right: 5px;
            letter-spacing: 0.3px;
        }
        .card-charged { border-top: 3px solid #ef4444; }
        .card-paid    { border-top: 3px solid #22c55e; }
        .card-balance { border-top: 3px solid #f59e0b; }
        .c-red       { color: #dc2626; }
        .c-green     { color: #16a34a; }
        .c-amber     { color: #d97706; }
        .c-green-neg { color: #16a34a; }

        /* ── Ledger ── */
        .section-label {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .ledger { width: 100%; border-collapse: collapse; }
        .ledger thead tr { background-color: #1e3a5f; }
        .ledger thead th {
            padding: 7px 10px;
            font-size: 6.5pt;
            font-weight: 600;
            text-align: left;
            color: #cbd5e1;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .ledger thead th.num { text-align: right; }
        .ledger tbody tr { border-bottom: 1px solid #f1f5f9; }
        .ledger tbody tr.inv-row { background-color: #fafafa; }
        .ledger tbody tr.pay-row { background-color: #ffffff; }
        .ledger tbody td {
            padding: 6px 10px;
            font-size: 7pt;
            vertical-align: middle;
            color: #334155;
        }
        .ledger tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .ledger tfoot tr { background-color: #1e3a5f; }
        .ledger tfoot td {
            padding: 7px 10px;
            font-size: 7.5pt;
            font-weight: bold;
            color: #fff;
        }
        .ledger tfoot td.num { text-align: right; }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 5.5pt;
            font-weight: bold;
            letter-spacing: 0.3px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .badge-inv { background: #e2e8f0; color: #475569; }
        .badge-pay { background: #dcfce7; color: #15803d; }

        .debit   { color: #dc2626; }
        .credit  { color: #16a34a; }
        .bal-pos { color: #dc2626; font-weight: bold; }
        .bal-neg { color: #16a34a; font-weight: bold; }
        .muted   { color: #94a3b8; }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            border-top: 1px solid #e2e8f0;
            padding: 6px 32px;
            font-size: 6pt;
            color: #94a3b8;
            background: #fff;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

{{-- Footer --}}
<div class="footer">
    <span class="footer-left">Generated {{ now()->format('d M Y, H:i') }} &nbsp;·&nbsp; Confidential</span>
    <span class="footer-right">{{ $schoolName }}</span>
</div>

{{-- Header — centered --}}
<div class="header">
    <img src="{{ $logoPath }}">
    <div class="school-name">{{ $schoolName }}</div>
    <div class="doc-title">Student Account Statement</div>
    <div class="stmt-date">Statement Date: <strong>{{ now()->format('d M Y') }}</strong></div>
</div>

{{-- Student info --}}
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td class="info-label">Student Name</td>
            <td class="info-val">{{ $user->first_name }} {{ $user->last_name }}</td>
            <td style="width:30px"></td>
            <td class="info-label">Admission No.</td>
            <td class="info-val">{{ $user->userID }}</td>
        </tr>
        <tr>
            <td class="info-label">Email</td>
            <td class="info-val">{{ $user->email }}</td>
            <td></td>
            <td class="info-label">Grade / Class</td>
            <td class="info-val">{{ $user->currentEnrollment?->gradeLevel?->name ?? '—' }} {{ $user->currentEnrollment?->stream?->name }}</td>
        </tr>
    </table>
</div>

{{-- Ledger --}}
<div class="section-label">Transaction History</div>
<table class="ledger">
    <thead>
    <tr>
        <th style="width:20px">#</th>
        <th style="width:68px">Date</th>
        <th style="width:120px">Reference</th>
        <th>Description</th>
        <th class="num" style="width:82px">Debit (KES)</th>
        <th class="num" style="width:82px">Credit (KES)</th>
        <th class="num" style="width:82px">Balance (KES)</th>
    </tr>
    </thead>
    <tbody>
    @foreach($ledger as $i => $row)
        <tr class="{{ $row['type'] === 'invoice' ? 'inv-row' : 'pay-row' }}">
            <td class="muted">{{ $i + 1 }}</td>
            <td class="muted">{{ $row['date'] }}</td>
            <td>
                @if($row['type'] === 'invoice')
                    <span class="badge badge-inv">INV</span>
                @else
                    <span class="badge badge-pay">RCT</span>
                @endif
                {{ $row['label'] }}
            </td>
            <td>{{ $row['description'] }}</td>
            {{-- Use {!! !!} so the <span> muted dash renders as HTML, not literal text --}}
            <td class="num debit">{!! $row['debit'] !== null ? number_format($row['debit'], 2) : '<span class="muted">—</span>' !!}</td>
            <td class="num credit">{!! $row['credit'] !== null ? number_format($row['credit'], 2) : '<span class="muted">—</span>' !!}</td>
            <td class="num {{ $row['balance'] > 0 ? 'bal-pos' : 'bal-neg' }}">{{ number_format($row['balance'], 2) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="4">Totals</td>
        <td class="num">{{ number_format($totals['total_charged'], 2) }}</td>
        <td class="num">{{ number_format($totals['total_paid'], 2) }}</td>
        <td class="num">{{ number_format($totals['balance'], 2) }}</td>
    </tr>
    </tfoot>
</table>

</body>
</html>

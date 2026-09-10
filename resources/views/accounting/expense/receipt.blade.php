<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #1e293b;
            background: #fff;
            padding: 24px 28px 50px;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid #1e3a5f;
        }
        .header img {
            max-height: 52px;
            display: block;
            margin: 0 auto 8px;
        }
        .school-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a5f;
            letter-spacing: 0.3px;
        }
        .school-motto {
            font-size: 7pt;
            font-style: italic;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            margin-top: 8px;
        }

        /* ── Receipt no. strip ── */
        .receipt-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .receipt-strip td { padding: 8px 12px; }
        .receipt-strip .no-cell {
            background: #1e3a5f;
            color: #fff;
            font-size: 9pt;
            font-weight: bold;
            text-align: right;
            border-radius: 3px;
        }
        .receipt-strip .no-label {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #cbd5e1;
            font-weight: normal;
            display: block;
            margin-bottom: 2px;
        }

        /* ── Info section ── */
        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 3px 10px 3px 0; font-size: 7.5pt; vertical-align: top; }
        .info-label { font-weight: bold; color: #475569; width: 95px; white-space: nowrap; }
        .info-val   { color: #1e293b; }

        /* ── Items table ── */
        .section-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items thead tr { background-color: #1e3a5f; }
        .items thead th {
            padding: 7px 10px;
            font-size: 7pt;
            font-weight: 600;
            text-align: left;
            color: #cbd5e1;
            letter-spacing: 0.3px;
        }
        .items thead th.num { text-align: right; }
        .items tbody tr:nth-child(even) { background-color: #fafafa; }
        .items tbody td {
            padding: 6px 10px;
            font-size: 7.5pt;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .items tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }

        /* ── Total bar ── */
        .total-bar {
            width: 100%;
            border-collapse: collapse;
            background: #1e3a5f;
            margin-bottom: 34px;
        }
        .total-bar td { padding: 10px 14px; color: #fff; }
        .total-bar .label { font-size: 8.5pt; }
        .total-bar .amount { font-size: 11pt; font-weight: bold; text-align: right; }

        /* ── Sign-off ── */
        .signoff { width: 100%; border-collapse: collapse; }
        .signoff td { width: 50%; text-align: center; font-size: 7pt; color: #475569; }
        .signoff .line {
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            margin: 0 16px;
            display: block;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            border-top: 1px solid #e2e8f0;
            padding: 6px 28px;
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
    <span class="footer-left">Generated {{ now()->format('d M Y, H:i') }} &nbsp;·&nbsp; Official Receipt</span>
    <span class="footer-right">{{ $schoolName }}</span>
</div>

{{-- Header --}}
<div class="header">
    @if($logoPath)
        <img src="{{ $logoPath }}">
    @endif
    <div class="school-name">{{ $schoolName }}</div>
    @if($schoolMotto)
        <div class="school-motto">{{ $schoolMotto }}</div>
    @endif
    <div class="doc-title">Purchase Voucher</div>
</div>

{{-- Receipt No. strip --}}
<table class="receipt-strip">
    <tr>
        <td></td>
        <td class="no-cell" style="width:180px;">
            <span class="no-label">Voucher No.</span>
            {{ $transaction->reference ?: $transaction->id }}
        </td>
    </tr>
</table>

{{-- Details --}}
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td class="info-label">Date</td>
            <td class="info-val">{{ $transaction->transaction_date->format('d M Y') }}</td>
            <td style="width:20px"></td>
            <td class="info-label">Term</td>
            <td class="info-val">Term {{ $transaction->term }}, {{ $transaction->academic_year }}</td>
        </tr>
        <tr>
            <td class="info-label">Vendor</td>
            <td class="info-val">{{ $transaction->vendor ?: '—' }}</td>
            <td></td>
            <td class="info-label">Payment Method</td>
            <td class="info-val">{{ $transaction->payment_method ? ucfirst($transaction->payment_method) : '—' }}</td>
        </tr>
    </table>
</div>

{{-- Items --}}
<div class="section-label">Items</div>
<table class="items">
    <thead>
    <tr>
        <th>Description</th>
        <th>Category</th>
        <th class="num" style="width:40px;">Qty</th>
        <th class="num" style="width:75px;">Unit Price</th>
        <th class="num" style="width:80px;">Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($transaction->items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td>{{ $item->category->name }}</td>
            <td class="num">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="num">{{ number_format($item->unit_price, 2) }}</td>
            <td class="num">{{ number_format($item->amount, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Total --}}
<table class="total-bar">
    <tr>
        <td class="label">Total Paid</td>
        <td class="amount">KES {{ number_format($transaction->total_amount, 2) }}</td>
    </tr>
</table>

{{-- Sign-off --}}
<table class="signoff">
    <tr>
        <td><span class="line">Prepared By ({{ trim($transaction->recordedBy->first_name.' '.$transaction->recordedBy->last_name) }})</span></td>
        <td><span class="line">Signature &amp; Stamp</span></td>
    </tr>
</table>

</body>
</html>

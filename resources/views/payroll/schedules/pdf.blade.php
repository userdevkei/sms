<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px 50px; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7.5pt;
            color: #1e293b;
            background: #fff;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1e3a5f;
        }
        .header img { max-height: 56px; display: block; margin: 0 auto 6px; }
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
        .stmt-date { font-size: 6.5pt; color: #94a3b8; margin-top: 5px; }
        .stmt-date strong { color: #1e3a5f; }

        /* ── Info block ── */
        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 14px;
            margin-bottom: 12px;
        }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 3px 10px 3px 0; font-size: 7pt; vertical-align: top; }
        .info-label { font-weight: bold; color: #475569; width: 90px; white-space: nowrap; }
        .info-val   { color: #1e293b; }

        /* ── Summary cards (table cells, DomPDF-safe) ── */
        .summary-row { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 16px; }
        .summary-row td.gap { width: 2%; padding: 0; border: none; }
        .summary-row td.card {
            width: 23.5%;
            border: 1px solid #e2e8f0;
            padding: 9px 14px 10px;
            vertical-align: top;
            background: #ffffff;
        }
        .card-label {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .card-value { font-size: 12pt; font-weight: bold; line-height: 1.2; white-space: nowrap; }
        .card-unit  { font-size: 7pt; font-weight: normal; color: #94a3b8; }
        .card-staff { border-top: 3px solid #1e3a5f !important; }
        .card-gross { border-top: 3px solid #3b82f6 !important; }
        .card-ded   { border-top: 3px solid #f59e0b !important; }
        .card-net   { border-top: 3px solid #22c55e !important; }
        .c-navy  { color: #1e3a5f; }
        .c-blue  { color: #2563eb; }
        .c-amber { color: #d97706; }
        .c-green { color: #16a34a; }

        /* ── Section label ── */
        .section-label {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 6px;
        }

        /* ── Schedule table ── */
        .ledger { width: 100%; border-collapse: collapse; }
        .ledger thead tr { background-color: #1e3a5f; }
        .ledger thead th {
            padding: 7px 5px;
            font-size: 6.5pt;
            font-weight: 600;
            text-align: left;
            color: #cbd5e1;
            letter-spacing: 0.2px;
            white-space: nowrap;
        }
        .ledger thead th.num { text-align: right; }
        .ledger tbody tr { border-bottom: 1px solid #f1f5f9; }
        .ledger tbody tr.alt { background-color: #fafafa; }
        .ledger tbody td {
            padding: 6px 5px;
            font-size: 7pt;
            vertical-align: middle;
            color: #334155;
        }
        .ledger tbody td.num { text-align: right; white-space: nowrap; }
        .ledger tfoot tr { background-color: #1e3a5f; }
        .ledger tfoot td {
            padding: 8px 5px;
            font-size: 7pt;
            font-weight: bold;
            color: #fff;
            white-space: nowrap;
        }
        .ledger tfoot td.num { text-align: right; }

        .net   { font-weight: bold; color: #15803d; }
        .muted { color: #94a3b8; }
        .mono  { font-family: 'DejaVu Sans Mono', monospace; font-size: 6.5pt; }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 5.5pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }
        .badge-bank  { background: #dbeafe; color: #1d4ed8; }
        .badge-mpesa { background: #dcfce7; color: #15803d; }
        .badge-draft { background: #fef3c7; color: #b45309; }
        .badge-final { background: #dcfce7; color: #15803d; }

        /* ── Employer contributions / remittance (table cells as boxes) ── */
        .remit-wrap { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 18px; page-break-inside: avoid; }
        .remit-wrap td.gap { width: 2%; padding: 0; }
        .remit-wrap td.box {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px 14px;
            vertical-align: top;
        }
        .remit-title {
            font-size: 6pt;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #1e3a5f;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .remit-table { width: 100%; border-collapse: collapse; }
        .remit-table td { padding: 4px 0; font-size: 7pt; border-bottom: 1px solid #e2e8f0; color: #334155; }
        .remit-table tr.last td { border-bottom: none; }
        .remit-table td.num { text-align: right; font-weight: bold; color: #1e3a5f; }
        .remit-note { font-size: 7pt; color: #475569; line-height: 1.7; }
        .remit-note strong { color: #1e3a5f; }

        /* ── Sign-off ── */
        .sign { width: 100%; border-collapse: collapse; margin-top: 36px; page-break-inside: avoid; }
        .sign td.line  { width: 22%; height: 30px; border-bottom: 1px solid #64748b; }
        .sign td.sp    { width: 3.99%; border: none; }
        .sign td.label {
            font-size: 6pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-top: 4px;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: -34px; left: 0; right: 0;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .footer table { width: 100%; border-collapse: collapse; }
        .footer td { font-size: 6pt; color: #94a3b8; }
        .footer td.l { text-align: left; width: 40%; }
        .footer td.c { text-align: center; width: 40%; }
        .footer td.r { text-align: right; width: 20%; }
    </style>
</head>
<body>
@php
    $schoolName = $schoolName ?? (setting('school_name') ?? config('app.name'));
    // Show a dash instead of 0.00
    $f = fn ($v) => (float) $v == 0 ? '–' : number_format($v, 2);

    $otherDed = fn ($l) => $l->pension + $l->other_deductions + $l->one_off_deduction;

    $totalOther      = $schedule->lines->sum(fn ($l) => $otherDed($l));
    $totalDeductions = $schedule->total_nssf + $schedule->total_shif + $schedule->total_housing_levy
                     + $schedule->total_paye + $totalOther;

    $erNssf    = $schedule->lines->sum('employer_nssf');
    $erHousing = $schedule->lines->sum('employer_housing_levy');
    $nita      = $schedule->lines->sum('nita');
@endphp

{{-- Page numbers (DomPDF does not support CSS counter(pages)) --}}
<script type="text/php">
    if (isset($pdf)) {
        $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
        $pdf->page_text(772, 572, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 6, [0.58, 0.64, 0.72]);
    }
</script>

{{-- Footer --}}
<div class="footer">
    <table>
        <tr>
            <td class="l">Generated {{ now()->format('d M Y, H:i') }} &nbsp;·&nbsp; Confidential – Payroll Data</td>
            <td class="c">{{ $schedule->title }}</td>
            <td class="r">{{ $schoolName }}</td>
        </tr>
    </table>
</div>

{{-- Header --}}
<div class="header">
    @if($logoPath)
        <img src="{{ $logoPath }}">
    @endif
    <div class="school-name">{{ $schoolName }}</div>
    <div class="doc-title">Staff Payment Schedule</div>
    <div class="stmt-date">Payroll Period: <strong>{{ $schedule->period->format('F Y') }}</strong></div>
</div>

{{-- Schedule info --}}
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td class="info-label">Schedule</td>
            <td class="info-val">{{ $schedule->title }}</td>
            <td style="width:30px"></td>
            <td class="info-label">Status</td>
            <td class="info-val">
                <span class="badge {{ $schedule->status === 'draft' ? 'badge-draft' : 'badge-final' }}">{{ strtoupper($schedule->status) }}</span>
            </td>
        </tr>
        <tr>
            <td class="info-label">Period</td>
            <td class="info-val">{{ $schedule->period->format('F Y') }}</td>
            <td></td>
            <td class="info-label">Remittance Due</td>
            <td class="info-val">{{ $schedule->remittanceDue()->format('d M Y') }}</td>
        </tr>
    </table>
</div>

{{-- Summary cards --}}
<table class="summary-row">
    <tr>
        <td class="card card-staff">
            <div class="card-label">Staff Paid</div>
            <div class="card-value c-navy">{{ $schedule->lines->count() }}</div>
        </td>
        <td class="gap"></td>
        <td class="card card-gross">
            <div class="card-label">Total Gross</div>
            <div class="card-value c-blue"><span class="card-unit">KES</span> {{ number_format($schedule->total_gross, 2) }}</div>
        </td>
        <td class="gap"></td>
        <td class="card card-ded">
            <div class="card-label">Total Deductions</div>
            <div class="card-value c-amber"><span class="card-unit">KES</span> {{ number_format($totalDeductions, 2) }}</div>
        </td>
        <td class="gap"></td>
        <td class="card card-net">
            <div class="card-label">Total Net Pay</div>
            <div class="card-value c-green"><span class="card-unit">KES</span> {{ number_format($schedule->total_net, 2) }}</div>
        </td>
    </tr>
</table>

{{-- Schedule table --}}
<div class="section-label">Payment Details</div>
<table class="ledger">
    <thead>
    <tr>
        <th style="width:2%">#</th>
        <th style="width:6%">Staff No</th>
        <th style="width:13%">Name</th>
        <th style="width:8%">KRA PIN</th>
        <th class="num" style="width:8%">Gross</th>
        <th class="num" style="width:6.5%">NSSF</th>
        <th class="num" style="width:6.5%">SHIF</th>
        <th class="num" style="width:6%">AHL</th>
        <th class="num" style="width:7%">PAYE</th>
        <th class="num" style="width:6.5%">Other Ded.</th>
        <th class="num" style="width:6%">Non-tax</th>
        <th class="num" style="width:8%">Net Pay</th>
        <th style="width:5%">Pay Via</th>
        <th style="width:11.5%">Account / Phone</th>
    </tr>
    </thead>
    <tbody>
    @foreach($schedule->lines as $i => $l)
        <tr class="{{ $i % 2 ? 'alt' : '' }}">
            <td class="muted">{{ $i + 1 }}</td>
            <td>{{ $l->staff_no }}</td>
            <td>{{ $l->full_name }}</td>
            <td class="mono">{{ $l->kra_pin }}</td>
            <td class="num">{{ $f($l->gross_pay) }}</td>
            <td class="num">{{ $f($l->nssf) }}</td>
            <td class="num">{{ $f($l->shif) }}</td>
            <td class="num">{{ $f($l->housing_levy) }}</td>
            <td class="num">{{ $f($l->paye) }}</td>
            <td class="num">{{ $f($otherDed($l)) }}</td>
            <td class="num">{{ $f($l->non_taxable_allowances) }}</td>
            <td class="num net">{{ number_format($l->net_pay, 2) }}</td>
            <td>
                <span class="badge {{ $l->payment_method === 'mpesa' ? 'badge-mpesa' : 'badge-bank' }}">{{ strtoupper($l->payment_method) }}</span>
            </td>
            <td class="mono">
                @if($l->payment_method === 'mpesa')
                    {{ $l->mpesa_phone }}
                @else
                    {{ $l->bank_name }}<br>{{ $l->bank_account }}
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td colspan="4">TOTAL</td>
        <td class="num">{{ number_format($schedule->total_gross, 2) }}</td>
        <td class="num">{{ number_format($schedule->total_nssf, 2) }}</td>
        <td class="num">{{ number_format($schedule->total_shif, 2) }}</td>
        <td class="num">{{ number_format($schedule->total_housing_levy, 2) }}</td>
        <td class="num">{{ number_format($schedule->total_paye, 2) }}</td>
        <td class="num">{{ number_format($totalOther, 2) }}</td>
        <td class="num">{{ number_format($schedule->lines->sum('non_taxable_allowances'), 2) }}</td>
        <td class="num">{{ number_format($schedule->total_net, 2) }}</td>
        <td colspan="2"></td>
    </tr>
    </tfoot>
</table>

{{-- Employer contributions & remittance --}}
<table class="remit-wrap">
    <tr>
        <td class="box" style="width:38%;">
            <div class="remit-title">Employer Contributions (KES)</div>
            <table class="remit-table">
                <tr><td>NSSF (Employer)</td><td class="num">{{ number_format($erNssf, 2) }}</td></tr>
                <tr><td>Housing Levy (Employer)</td><td class="num">{{ number_format($erHousing, 2) }}</td></tr>
                <tr class="last"><td>NITA</td><td class="num">{{ number_format($nita, 2) }}</td></tr>
            </table>
        </td>
        <td class="gap"></td>
        <td class="box" style="width:60%;">
            <div class="remit-title">Statutory Remittance</div>
            <div class="remit-note">
                All statutory deductions are due by <strong>{{ $schedule->remittanceDue()->format('d F Y') }}</strong>.<br>
                <strong>PAYE</strong> → KRA iTax &nbsp;·&nbsp;
                <strong>NSSF</strong> → NSSF &nbsp;·&nbsp;
                <strong>SHIF</strong> → SHA &nbsp;·&nbsp;
                <strong>Housing Levy</strong> → KRA &nbsp;·&nbsp;
                <strong>NITA</strong> → NITA
            </div>
        </td>
    </tr>
</table>

{{-- Sign-off --}}
<table class="sign">
    <tr>
        <td class="line">&nbsp;</td><td class="sp"></td>
        <td class="line">&nbsp;</td><td class="sp"></td>
        <td class="line">&nbsp;</td><td class="sp"></td>
        <td class="line">&nbsp;</td>
    </tr>
    <tr>
        <td class="label">Prepared by</td><td class="sp"></td>
        <td class="label">Checked by</td><td class="sp"></td>
        <td class="label">Approved by</td><td class="sp"></td>
        <td class="label">Date</td>
    </tr>
</table>

</body>
</html>

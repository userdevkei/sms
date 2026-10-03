@php
    $b = $s['brand']; $c = $b['color'];
    $n = fn ($v) => number_format((float) $v, 2);
    $t = $s['tax'];
@endphp

@include('payroll.docs._header', [
    'b' => $b, 'title' => 'PAYSLIP', 'sub' => $s['period_label'],
    'meta' => [['Ref', $s['ref']], ['Period', $s['period_range']], ['Status', $s['status']]],
])

{{-- Employee --}}
<table width="100%" style="margin-bottom:8px;">
    <tr><td colspan="4" class="th" style="background-color:{{ $c }};">EMPLOYEE DETAILS</td></tr>
    <tr><td class="k">Employee</td><td class="v"><b>{{ $s['name'] }}</b></td><td class="k">Staff No</td><td class="v">{{ $s['staff_no'] ?: '—' }}</td></tr>
    <tr><td class="k">Designation</td><td class="v">{{ $s['designation'] ?: '—' }}</td><td class="k">Department</td><td class="v">{{ $s['department'] ?: '—' }}</td></tr>
    <tr><td class="k">National ID</td><td class="v">{{ $s['id_number'] ?: '—' }}</td><td class="k">Employment</td><td class="v">{{ $s['emp_type'] ?: '—' }}</td></tr>
    <tr><td class="k">KRA PIN</td><td class="v">{{ $s['kra_pin'] ?: '—' }}</td><td class="k">NSSF No</td><td class="v">{{ $s['nssf_no'] ?: '—' }}</td></tr>
    <tr><td class="k">SHA / SHIF No</td><td class="v">{{ $s['shif_no'] ?: '—' }}</td><td class="k">Days worked</td><td class="v">{{ $s['days'] }}</td></tr>
    <tr><td class="k">Paid via</td><td class="v" colspan="3">{{ $s['pay_method'] }}</td></tr>
</table>

{{-- Earnings | Deductions --}}
<table width="100%">
    <tr>
        <td width="50%" valign="top" style="padding-right:5px;">
            <table width="100%">
                <tr><td class="th" style="background-color:{{ $c }};">EARNINGS</td><td class="th r" style="background-color:{{ $c }};">KES</td></tr>
                @foreach($s['earnings'] as [$label, $amt])
                    <tr class="row"><td>{{ $label }}</td><td class="r">{{ $n($amt) }}</td></tr>
                @endforeach
                <tr class="sub"><td>Gross pay (taxable)</td><td class="r">{{ $n($s['gross']) }}</td></tr>
                @foreach($s['non_taxable'] as [$label, $amt])
                    <tr class="row"><td>{{ $label }}</td><td class="r">{{ $n($amt) }}</td></tr>
                @endforeach
                <tr class="sub"><td>Total earnings</td><td class="r">{{ $n($s['total_earnings']) }}</td></tr>
            </table>
        </td>
        <td width="50%" valign="top" style="padding-left:5px;">
            <table width="100%">
                <tr><td class="th" style="background-color:{{ $c }};">DEDUCTIONS</td><td class="th r" style="background-color:{{ $c }};">KES</td></tr>
                @foreach($s['deductions'] as [$label, $amt])
                    <tr class="row"><td>{{ $label }}</td><td class="r">{{ $n($amt) }}</td></tr>
                @endforeach
                <tr class="sub"><td>Total deductions</td><td class="r">{{ $n($s['total_deductions']) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Net pay --}}
<table width="100%" style="margin-top:8px;">
    <tr>
        <td style="background-color:{{ $c }}; color:#ffffff; padding:9px 12px; font-size:9pt; font-weight:bold;">NET PAY</td>
        <td style="background-color:{{ $c }}; color:#ffffff; padding:9px 12px; text-align:right; font-size:10pt; font-weight:bold;">KES {{ $n($s['net']) }}</td>
    </tr>
    <tr><td colspan="2" style="background-color:#f1f5f9; padding:5px 12px; font-size:8pt; color:#444;"><b>In words:</b> {{ $s['net_words'] }}</td></tr>
</table>

{{-- PAYE working | Employer + YTD --}}
<table width="100%" style="margin-top:8px;">
    <tr>
        <td width="50%" valign="top" style="padding-right:5px;">
            <table width="100%">
                <tr><td class="th" style="background-color:{{ $c }};">PAYE COMPUTATION</td><td class="th r" style="background-color:{{ $c }};">KES</td></tr>
                <tr class="row"><td>Gross pay</td><td class="r">{{ $n($t['gross']) }}</td></tr>
                <tr class="row"><td class="muted">Less: NSSF</td><td class="r">{{ $n($t['nssf']) }}</td></tr>
                <tr class="row"><td class="muted">Less: SHIF</td><td class="r">{{ $n($t['shif']) }}</td></tr>
                <tr class="row"><td class="muted">Less: Affordable Housing Levy</td><td class="r">{{ $n($t['ahl']) }}</td></tr>
                @if($t['pension'] > 0)<tr class="row"><td class="muted">Less: Pension (allowable)</td><td class="r">{{ $n($t['pension']) }}</td></tr>@endif
                @if($t['mortgage'] > 0)<tr class="row"><td class="muted">Less: Mortgage interest</td><td class="r">{{ $n($t['mortgage']) }}</td></tr>@endif
                <tr class="sub"><td>Taxable (chargeable) pay</td><td class="r">{{ $n($t['taxable']) }}</td></tr>
                <tr class="row"><td>Tax charged (bands)</td><td class="r">{{ $n($t['charged']) }}</td></tr>
                <tr class="row"><td class="muted">Less: Personal relief</td><td class="r">{{ $n($t['personal']) }}</td></tr>
                @if($t['insurance'] > 0)<tr class="row"><td class="muted">Less: Insurance relief</td><td class="r">{{ $n($t['insurance']) }}</td></tr>@endif
                <tr class="sub"><td>PAYE payable</td><td class="r">{{ $n($t['paye']) }}</td></tr>
            </table>
        </td>
        <td width="50%" valign="top" style="padding-left:5px;">
            <table width="100%">
                <tr><td class="th" style="background-color:{{ $c }};">YEAR TO DATE ({{ $s['ytd_label'] }})</td><td class="th r" style="background-color:{{ $c }};">KES</td></tr>
                <tr class="row"><td>Gross pay</td><td class="r">{{ $n($s['ytd']->gross) }}</td></tr>
                <tr class="row"><td>NSSF</td><td class="r">{{ $n($s['ytd']->nssf) }}</td></tr>
                <tr class="row"><td>SHIF</td><td class="r">{{ $n($s['ytd']->shif) }}</td></tr>
                <tr class="row"><td>Affordable Housing Levy</td><td class="r">{{ $n($s['ytd']->ahl) }}</td></tr>
                <tr class="row"><td>PAYE</td><td class="r">{{ $n($s['ytd']->paye) }}</td></tr>
                <tr class="sub"><td>Net pay</td><td class="r">{{ $n($s['ytd']->net) }}</td></tr>
            </table>
            <table width="100%" style="margin-top:8px;">
                <tr><td class="th" style="background-color:{{ $c }};">EMPLOYER CONTRIBUTIONS</td><td class="th r" style="background-color:{{ $c }};">KES</td></tr>
                <tr class="row"><td>NSSF (employer)</td><td class="r">{{ $n($s['employer']['nssf']) }}</td></tr>
                <tr class="row"><td>Affordable Housing Levy (employer)</td><td class="r">{{ $n($s['employer']['ahl']) }}</td></tr>
                <tr class="row"><td>NITA levy</td><td class="r">{{ $n($s['employer']['nita']) }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div style="margin-top:10px; font-size:7.5pt; color:#777; border-top:0.5pt solid #ccc; padding-top:4px;">
    This is a computer-generated payslip and does not require a signature. Statutory deductions are calculated under the Income Tax Act (PAYE),
    NSSF Act, Social Health Insurance Act and Affordable Housing Act. Please report any discrepancy to the Finance office within 7 days.
</div>

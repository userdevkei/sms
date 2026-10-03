@php
    $b = $d['brand']; $c = $b['color']; $p = $d['payee']; $T = $d['totals'];
    $n = fn ($v) => ((float) $v) == 0 ? '-' : number_format((float) $v, 2);
    $cols = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n'];
@endphp

@include('payroll.docs._header', [
    'b' => $b, 'title' => 'TAX DEDUCTION CARD', 'sub' => 'P9 · Year ' . $d['year'],
    'meta' => [['Tax year', '1 Jan – 31 Dec ' . $d['year']]],
])

<table width="100%" style="margin-bottom:6px;">
    <tr>
        <td class="k">Employer's name</td><td class="v"><b>{{ $b['name'] }}</b></td>
        <td class="k">Employer's PIN</td><td class="v">{{ $b['pin'] ?: '—' }}</td>
        <td class="k">Employment</td><td class="v">{{ ucfirst($p->employment_type) }}</td>
    </tr>
    <tr>
        <td class="k">Employee's name</td><td class="v"><b>{{ $p->full_name }}</b></td>
        <td class="k">Employee's PIN</td><td class="v">{{ $p->kra_pin ?: '—' }}</td>
        <td class="k">Staff No</td><td class="v">{{ $p->staff_no ?: '—' }}</td>
    </tr>
</table>

<table width="100%">
    <thead>
    <tr>
        <td class="h" rowspan="2" style="background-color:{{ $c }}; width:7%;">MONTH</td>
        <td class="h" colspan="4" style="background-color:{{ $c }};">GROSS PAY</td>
        <td class="h" colspan="5" style="background-color:#2b6a9c;">ALLOWABLE DEDUCTIONS</td>
        <td class="h" colspan="5" style="background-color:{{ $c }};">TAX COMPUTATION</td>
    </tr>
    <tr>
        <td class="h" style="background-color:{{ $c }};">A<br>Basic / cash pay</td>
        <td class="h" style="background-color:{{ $c }};">B<br>Benefits non-cash</td>
        <td class="h" style="background-color:{{ $c }};">C<br>Value of quarters</td>
        <td class="h" style="background-color:{{ $c }};">D<br>Total gross pay<br>(A+B+C)</td>
        <td class="h" style="background-color:#2b6a9c;">E<br>Retirement contrib.<br>(NSSF + pension)</td>
        <td class="h" style="background-color:#2b6a9c;">F<br>Affordable housing levy</td>
        <td class="h" style="background-color:#2b6a9c;">G<br>SHIF</td>
        <td class="h" style="background-color:#2b6a9c;">H<br>Owner-occupied interest</td>
        <td class="h" style="background-color:#2b6a9c;">I<br>Total deductions<br>(E+F+G+H)</td>
        <td class="h" style="background-color:{{ $c }};">J<br>Chargeable pay<br>(D&minus;I)</td>
        <td class="h" style="background-color:{{ $c }};">K<br>Tax charged</td>
        <td class="h" style="background-color:{{ $c }};">L<br>Personal relief</td>
        <td class="h" style="background-color:{{ $c }};">M<br>Insurance relief</td>
        <td class="h" style="background-color:{{ $c }};">N<br>PAYE tax<br>(K&minus;L&minus;M)</td>
    </tr>
    </thead>
    <tbody>
    @foreach($d['months'] as $m => $row)
        <tr>
            <td class="m" style="background-color:{{ $loop->even ? '#f6f8fb' : '#ffffff' }};">{{ \Carbon\Carbon::create($d['year'], $m, 1)->format('F') }}</td>
            @foreach($cols as $col)
                <td class="c" style="background-color:{{ $loop->parent->even ? '#f6f8fb' : '#ffffff' }};">{{ is_array($row) ? $n($row[$col]) : '-' }}</td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr class="tot">
        <td style="text-align:left;">TOTAL</td>
        @foreach($cols as $col)<td>{{ number_format($T[$col], 2) }}</td>@endforeach
    </tr>
    </tfoot>
</table>

<table width="100%" style="margin-top:8px;">
    <tr>
        <td width="50%" valign="top" style="padding-right:8px;">
            <table width="100%" style="border:0.5pt solid #9fb1c6;">
                <tr><td style="padding:5px 8px; background-color:#f1f5f9;">Total chargeable pay (Column J)</td><td style="padding:5px 8px; text-align:right; font-weight:bold;">KES {{ number_format($T['j'], 2) }}</td></tr>
                <tr><td style="padding:5px 8px; background-color:#f1f5f9;">Total PAYE tax (Column N)</td><td style="padding:5px 8px; text-align:right; font-weight:bold;">KES {{ number_format($T['n'], 2) }}</td></tr>
            </table>
            <div style="margin-top:6px; font-size:6.8pt; color:#666; line-height:1.5;">
                Column E combines NSSF with any registered pension contribution; column H is owner-occupied mortgage interest, both within the statutory caps in force
                when each month was approved. Non-cash benefits and value of quarters (B, C) are not recorded by this payroll and show as nil.
                This card is generated from approved payment schedules only; reconcile it with the PAYE returns filed on iTax before issuing.
            </div>
        </td>
        <td width="50%" valign="bottom" style="padding-left:8px;">
            <table width="100%">
                <tr>
                    <td style="border-bottom:0.5pt solid #444; height:34px;">&nbsp;</td><td width="20">&nbsp;</td>
                    <td style="border-bottom:0.5pt solid #444;">&nbsp;</td>
                </tr>
                <tr>
                    <td style="font-size:7pt; color:#555; padding-top:2px;">Employer's signature &amp; stamp</td><td></td>
                    <td style="font-size:7pt; color:#555; padding-top:2px;">Date</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

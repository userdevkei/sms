<table class="t" cellpadding="0" cellspacing="0">
    @if ($showHead)
        <thead>
        <tr>
            <th width="5%">#</th>
            <th width="11%">Adm No</th>
            <th width="25%" style="text-align:left">Student</th>
            <th width="10%">Grade</th>
            <th width="9%">Stream</th>
            <th width="11%" class="r">Invoiced</th>
            <th width="11%" class="r">Paid</th>
            <th width="12%" class="r">Balance</th>
            <th width="6%" class="r">Bal %</th>
        </tr>
        </thead>
    @endif
    <tbody>
    @foreach ($rows as $r)
        @php $tone = $r->balance_pct >= 75 ? 'hi' : ($r->balance_pct >= 50 ? 'mid' : ''); @endphp
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="c">{{ $offset + $loop->iteration }}</td>
            <td>{{ $r->admission_no }}</td>
            <td>{{ $r->student_name }}</td>
            <td class="c">{{ $r->grade_name }}</td>
            <td class="c">{{ $r->stream_name }}</td>
            <td class="r">{{ number_format($r->invoiced, 2) }}</td>
            <td class="r">{{ number_format($r->paid, 2) }}</td>
            <td class="r"><b>{{ number_format($r->balance, 2) }}</b></td>
            <td class="r {{ $tone }}">{{ number_format($r->balance_pct, 1) }}%</td>
        </tr>
    @endforeach

    @if ($totals)
        <tr class="{{ $totalClass }}">
            <td colspan="5" class="r">{{ $totalLabel }} ({{ $totals['count'] }})</td>
            <td class="r">{{ number_format($totals['invoiced'], 2) }}</td>
            <td class="r">{{ number_format($totals['paid'], 2) }}</td>
            <td class="r">{{ number_format($totals['balance'], 2) }}</td>
            <td class="r">{{ number_format($totals['pct'], 1) }}%</td>
        </tr>
    @endif
    </tbody>
</table>

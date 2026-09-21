<div style="text-align:center">
    <div style="font-size:15pt;font-weight:bold">{{ $school }}</div>
    <div style="font-size:12pt;font-weight:bold">DEBTORS LIST</div>
    <div style="font-size:8.5pt;color:#555;margin-top:2px">{{ $filters }}</div>
</div>

<table class="kpi" style="width:100%;margin:8px 0 6px" cellpadding="5">
    <tr>
        <td>Debtors<br><b style="font-size:11pt">{{ number_format($grand['count']) }}</b></td>
        <td>Invoiced ({{ $currency }})<br><b style="font-size:11pt">{{ number_format($grand['invoiced'], 2) }}</b></td>
        <td>Collected ({{ $currency }})<br><b style="font-size:11pt">{{ number_format($grand['paid'], 2) }}</b></td>
        <td>Outstanding ({{ $currency }})<br><b class="hi" style="font-size:11pt">{{ number_format($grand['balance'], 2) }}</b>
            <span style="color:#666"> ({{ $grand['pct'] }}% of invoiced)</span></td>
    </tr>
</table>

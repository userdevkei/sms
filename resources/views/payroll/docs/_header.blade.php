<table width="100%" style="border-collapse:collapse; margin-bottom:8px;">
    <tr>
        <td width="66" style="vertical-align:middle; padding:0 8px 8px 0;">
            @if($b['logo'])
                <img src="{{ $b['logo'] }}" style="height:54px;">
            @endif
        </td>
        <td style="vertical-align:middle; padding-bottom:8px;">
            <div style="font-size:15pt; font-weight:bold; color:{{ $b['color'] }};">{{ $b['name'] }}</div>
            @if($b['tagline'])<div style="font-size:8pt; color:#777; font-style:italic;">{{ $b['tagline'] }}</div>@endif
            <div style="font-size:8pt; color:#555;">{{ implode('  ·  ', array_filter([$b['address'], $b['phone'], $b['email']])) }}</div>
            <div style="font-size:8pt; color:#555;">Employer KRA PIN: <b>{{ $b['pin'] ?: '—' }}</b></div>
        </td>
        <td width="200" style="background-color:{{ $b['color'] }}; color:#ffffff; padding:8px 12px; text-align:right; vertical-align:middle;">
            <div style="font-size:10pt; font-weight:bold;">{{ $title }}</div>
            <div style="font-size:9pt;">{{ $sub }}</div>
            @foreach($meta as [$k, $v])
                <div style="font-size:7.5pt;">{{ $k }}: {{ $v }}</div>
            @endforeach
        </td>
    </tr>
    <tr><td colspan="3" style="border-top:3px solid {{ $b['color'] }}; font-size:1pt; line-height:1pt;">&nbsp;</td></tr>
</table>

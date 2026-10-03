<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 7.5pt; color: #222; }
        table { border-collapse: collapse; }
        .k { color:#777; font-size:7.5pt; padding:3px 6px; border-bottom:0.5pt solid #e3e3e3; width:14%; }
        .v { padding:3px 6px; border-bottom:0.5pt solid #e3e3e3; width:19%; }
        .h { color:#ffffff; font-weight:bold; font-size:6.8pt; padding:4px 3px; text-align:center; border:0.5pt solid #ffffff; }
        .c { border:0.5pt solid #c9d3de; padding:4px 4px; text-align:right; }
        .m { border:0.5pt solid #c9d3de; padding:4px 6px; font-weight:bold; }
        .tot td { background-color:#e8eef5; font-weight:bold; border:0.5pt solid #9fb1c6; padding:5px 4px; text-align:right; }
    </style>
</head>
<body>
@foreach($cards as $d)
    @include('payroll.docs.p9', ['d' => $d])
    @if(! $loop->last)<pagebreak />@endif
@endforeach
</body>
</html>

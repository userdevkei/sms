<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 9pt; color: #222; }
        table { border-collapse: collapse; }
        .k { color:#777; font-size:8pt; padding:3px 6px; border-bottom:0.5pt solid #e3e3e3; width:17%; }
        .v { padding:3px 6px; border-bottom:0.5pt solid #e3e3e3; width:33%; }
        .th { color:#ffffff; font-weight:bold; font-size:8pt; padding:5px 8px; }
        .r { text-align:right; }
        .row td { padding:4px 8px; border-bottom:0.5pt solid #eeeeee; }
        .sub td { padding:5px 8px; font-weight:bold; background-color:#f1f5f9; border-top:0.5pt solid #c9d3de; }
        .muted { color:#777; }
    </style>
</head>
<body>
@foreach($slips as $s)
    @include('payroll.docs.payslip', ['s' => $s])
    @if(! $loop->last)<pagebreak />@endif
@endforeach
</body>
</html>

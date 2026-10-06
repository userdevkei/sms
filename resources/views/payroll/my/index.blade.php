@extends('layouts.app')
@section('content')
    @php
        $n = fn ($v) => number_format((float) $v, 2);
        $deductions = fn ($l) => $l->nssf + $l->shif + $l->housing_levy + $l->paye + $l->pension + $l->other_deductions + $l->one_off_deduction;
    @endphp

    <style>
        .my-stat { border:0; box-shadow:0 1px 3px rgba(0,0,0,.08); }
        .my-stat .lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; }
        .my-stat .val { font-size:1.15rem; font-weight:700; font-variant-numeric:tabular-nums; }
        .my-table thead th { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; background:#f8f9fa; white-space:nowrap; }
        .my-table td { font-size:.88rem; vertical-align:middle; }
        .my-table .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    </style>

    <div class="container-fluid">
        <div class="mb-3">
            <h5 class="mb-0">My Payslips &amp; P9</h5>
            <div class="small text-muted">Your approved payroll documents. View them in the browser or download a PDF copy.</div>
        </div>

        @if(! $payee)
            <div class="alert alert-warning small">
                <i class="bi bi-info-circle"></i> Your account is not linked to a payroll record yet, so there is nothing to show.
                Please contact the Finance office.
            </div>
        @else
            {{-- Summary --}}
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3"><div class="card my-stat"><div class="card-body py-2 px-3">
                            <div class="lbl">Latest net pay</div>
                            <div class="val">{{ $stats['latest'] ? $n($stats['latest']->net_pay) : '—' }}</div>
                            <div class="small text-muted">{{ $stats['latest'] ? $stats['latest']->schedule->period->format('F Y') : 'No payslips yet' }}</div>
                        </div></div></div>
                <div class="col-6 col-md-3"><div class="card my-stat"><div class="card-body py-2 px-3">
                            <div class="lbl">{{ $year }} gross pay</div><div class="val">{{ $n($stats['gross']) }}</div>
                        </div></div></div>
                <div class="col-6 col-md-3"><div class="card my-stat"><div class="card-body py-2 px-3">
                            <div class="lbl">{{ $year }} PAYE</div><div class="val">{{ $n($stats['paye']) }}</div>
                        </div></div></div>
                <div class="col-6 col-md-3"><div class="card my-stat"><div class="card-body py-2 px-3">
                            <div class="lbl">{{ $year }} net pay</div><div class="val">{{ $n($stats['net']) }}</div>
                        </div></div></div>
            </div>

            {{-- Payslips --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold"><i class="bi bi-receipt"></i> Payslips</span>
                    @if($years->count())
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0">Year</label>
                            <select name="year" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                                @foreach($years as $y)<option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>@endforeach
                            </select>
                        </form>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover my-table mb-0 fs-sm w-100 table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th class="ps-3">Period</th>
                            <th class="num">Gross pay</th>
                            <th class="num">Deductions</th>
                            <th class="num">Net pay</th>
                            <th>Status</th>
                            <th class="text-end pe-3" style="width:170px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($lines as $l)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="ps-3 fw-semibold">{{ $l->schedule->period->format('F Y') }}</td>
                                <td class="num">{{ $n($l->gross_pay) }}</td>
                                <td class="num">{{ $n($deductions($l)) }}</td>
                                <td class="num fw-bold">{{ $n($l->net_pay) }}</td>
                                <td>
                                    @if($l->schedule->status === 'paid')
                                        <span class="badge bg-success">Paid{{ $l->schedule->paid_on ? ' · ' . $l->schedule->paid_on->format('d M') : '' }}</span>
                                    @else
                                        <span class="badge bg-info text-dark">Approved</span>
                                    @endif
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('my-payroll.payslip', $l) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                    <a href="{{ route('my-payroll.payslip', [$l, 'download' => 1]) }}" class="btn btn-sm btn-primary"><i class="bi bi-download"></i> Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payslips available{{ $years->count() ? " for {$year}" : ' yet' }}.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- P9 --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2"><span class="fw-semibold"><i class="bi bi-file-earmark-text"></i> P9 tax deduction cards</span></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover my-table mb-0 fs-sm w-100 table-striped">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th class="ps-3">Tax year</th>
                            <th class="text-center">Months</th>
                            <th class="num">Gross pay</th>
                            <th class="num">PAYE</th>
                            <th class="text-end pe-3" style="width:170px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($p9Rows as $r)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="ps-3 fw-semibold">{{ $r['year'] }}</td>
                                <td class="text-center">{{ $r['months'] }}/12</td>
                                <td class="num">{{ $n($r['gross']) }}</td>
                                <td class="num">{{ $n($r['paye']) }}</td>
                                <td class="text-end pe-3 text-nowrap">
                                    <a href="{{ route('my-payroll.p9', $r['year']) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> View</a>
                                    <a href="{{ route('my-payroll.p9', [$r['year'], 'download' => 1]) }}" class="btn btn-sm btn-primary"><i class="bi bi-download"></i> Download</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No P9 available yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-muted">
                    A P9 for the current year only covers months already approved, so it grows as each month is processed. The final P9 is complete after the December payroll.
                </div>
            </div>
        @endif
    </div>
@endsection

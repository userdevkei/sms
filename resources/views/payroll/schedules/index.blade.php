@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Staff Payment Schedules</h4>
        <a href="{{ route('payroll.payees.index') }}" class="btn btn-outline-secondary">Manage Staff Payees</a>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="POST" action="{{ route('payroll.schedules.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3"><label class="form-label">Month</label><input type="month" name="period" value="{{ now()->format('Y-m') }}" class="form-control" required></div>
            <div class="col-md-5"><label class="form-label">Title (optional)</label><input name="title" class="form-control" placeholder="Staff Payment Schedule – October 2026"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Generate</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Period</th><th>Title</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($schedules as $s)
                <tr>
                    <td>{{ $s->period->format('M Y') }}</td><td>{{ $s->title }}</td>
                    <td class="text-end">{{ number_format($s->total_gross, 2) }}</td>
                    <td class="text-end">{{ number_format($s->total_paye, 2) }}</td>
                    <td class="text-end">{{ number_format($s->total_net, 2) }}</td>
                    <td><span class="badge bg-{{ ['draft'=>'secondary','approved'=>'info','paid'=>'success'][$s->status] }}">{{ ucfirst($s->status) }}</span></td>
                    <td class="text-end"><a href="{{ route('payroll.schedules.show', $s) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No schedules yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@endsection

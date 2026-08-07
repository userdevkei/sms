@extends('layouts.app')
@section('title', 'Fee Statement')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Fee Statement \u2014 {{ $student->full_name }}</h1>
        <p class="text-muted mb-0">{{ $student->userID ?: '\u2014' }}</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="app-stat-card"><div class="text-muted small">Total Billed</div><div class="h5 mb-0">KES {{ number_format($totalBilled, 2) }}</div></div></div>
        <div class="col-md-4"><div class="app-stat-card"><div class="text-muted small">Total Paid</div><div class="h5 mb-0 text-success">KES {{ number_format($totalPaid, 2) }}</div></div></div>
        <div class="col-md-4"><div class="app-stat-card"><div class="text-muted small">Outstanding Balance</div><div class="h5 mb-0 {{ $totalBalance > 0 ? 'text-danger' : '' }}">KES {{ number_format($totalBalance, 2) }}</div></div></div>
    </div>

    @foreach($invoices as $invoice)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">{{ $invoice->invoice_number }} \u2014 Term {{ $invoice->term }}, {{ $invoice->academic_year }}</h6>
                    @php $map = ['unpaid' => 'danger', 'partially_paid' => 'warning', 'paid' => 'success', 'cancelled' => 'secondary']; @endphp
                    <span class="badge bg-{{ $map[$invoice->status] }}-subtle text-{{ $map[$invoice->status] }} text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span>
                </div>
                <table class="table table-sm mb-0">
                    <tbody>
                    @foreach($invoice->items as $item)
                        <tr class="{{ $item->amount < 0 ? 'text-success' : '' }}">
                            <td>{{ $item->description }}</td>
                            <td class="text-end">{{ $item->amount < 0 ? '-' : '' }}KES {{ number_format(abs($item->amount), 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-semibold"><td>Total</td><td class="text-end">KES {{ number_format($invoice->total_amount, 2) }}</td></tr>
                    <tr><td>Paid</td><td class="text-end text-success">KES {{ number_format($invoice->amount_paid, 2) }}</td></tr>
                    <tr class="fw-bold"><td>Balance</td><td class="text-end">KES {{ number_format($invoice->balance, 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection

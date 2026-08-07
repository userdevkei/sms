@extends('layouts.app')
@section('title', $invoice->invoice_number)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $invoice->invoice_number }}
                @php $map = ['unpaid' => 'danger', 'partially_paid' => 'warning', 'paid' => 'success', 'cancelled' => 'secondary']; @endphp
                <span class="badge bg-{{ $map[$invoice->status] }}-subtle text-{{ $map[$invoice->status] }} text-capitalize">{{ str_replace('_', ' ', $invoice->status) }}</span>
            </h1>
            <p class="text-muted mb-0">{{ $invoice->student->full_name }} ({{ $invoice->student->userID ?: '-' }}) . {{ $invoice->gradeLevel->name }} . Term {{ $invoice->term }}, {{ $invoice->academic_year }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('finance.invoices.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @can('payments.record')
                @if($invoice->balance > 0)
                    <a href="{{ route('finance.payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-cash me-1"></i> Record Payment</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Invoice Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Description</th><th>Source</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                            @foreach($invoice->items as $item)
                                <tr class="{{ $item->amount < 0 ? 'text-success' : '' }}">
                                    <td>{{ $item->description }}</td>
                                    <td><span class="badge bg-light text-muted text-capitalize">{{ str_replace('_', ' ', $item->source_type) }}</span></td>
                                    <td class="text-end">{{ $item->amount < 0 ? '-' : '' }}KES {{ number_format(abs($item->amount), 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr class="fw-semibold"><td colspan="2">Total</td><td class="text-end">KES {{ number_format($invoice->total_amount, 2) }}</td></tr>
                            <tr><td colspan="2">Paid</td><td class="text-end text-success">KES {{ number_format($invoice->amount_paid, 2) }}</td></tr>
                            <tr class="fw-bold"><td colspan="2">Balance</td><td class="text-end">KES {{ number_format($invoice->balance, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small mb-3">Payments</h6>
                    @forelse($invoice->payments as $payment)
                        <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                            <div>
                                <div class="fw-semibold">KES {{ number_format($payment->amount, 2) }} <span class="badge bg-light text-muted text-capitalize">{{ $payment->method }}</span></div>
                                <div class="text-muted small">{{ $payment->payment_number }} . {{ $payment->paid_on->format('d M Y') }} . by {{ $payment->receivedBy->full_name }}</div>
                                @if($payment->reference_number)<div class="text-muted small">Ref: {{ $payment->reference_number }}</div>@endif
                            </div>
                            @can('payments.manage')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-payment" data-url="{{ route('finance.payments.destroy', $payment->id) }}"><i class="bi bi-trash"></i></button>
                            @endcan
                        </div>
                    @empty
                        <p class="text-muted small fst-italic mb-0">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.btn-delete-payment').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!confirm('Remove this payment? The invoice balance will be recalculated.')) return;
                fetch(this.dataset.url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } })
                    .then(r => r.json()).then(res => res.success ? location.reload() : alert(res.message));
            });
        });
    </script>
@endpush

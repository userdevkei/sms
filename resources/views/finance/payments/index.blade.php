{{--
@extends('layouts.app')
@section('title', 'Payments')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Payments</h1>
        <p class="text-muted mb-0">Every payment recorded across all invoices.</p>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Method</label>
                <select id="filterMethod" class="form-select form-select-sm">
                    <option value="">All Methods</option><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="bank">Bank</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-hover align-middle w-100">
                    <thead><tr><th>#</th><th>Receipt No.</th><th>Invoice</th><th>Student</th><th>Method</th><th>Amount</th><th>Reference</th><th>Date</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const table = $('#paymentsTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: @json(route('finance.payments.data')), data: d => { d.filter_method = $('#filterMethod').val(); } },
            order: [[0, 'desc']],
            columns: [
                { data: 'sn', orderable: false }, { data: 'payment_number' }, { data: 'invoice_number' },
                { data: 'student' }, { data: 'method', render: m => `<span class="text-capitalize">${m}</span>` },
                { data: 'amount' }, { data: 'reference' }, { data: 'paid_on' },
            ],
            language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...' }
        });
        $('#filterMethod').on('change', () => table.ajax.reload());
    </script>
@endpush
--}}


@extends('layouts.app')
@section('title', 'Payments')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Payments</h1>
        <p class="text-muted mb-0">All recorded payments and M-Pesa attempts across all students.</p>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#successful-pane">Successful ({{ $successfulPayments->count() }})</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#failed-pane">Failed / Pending ({{ $failedAttempts->count() }})</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="successful-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="successfulTable" class="table table-hover align-middle w-100">
                            <thead><tr><th>Date</th><th>Student</th><th>Invoice</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th><th>Received By</th></tr></thead>
                            <tbody>
                            @foreach($successfulPayments as $p)
                                <tr>
                                    <td data-order="{{ ($p->paid_on ?? $p->created_at)->format('Y-m-d') }}">{{ ($p->paid_on ?? $p->created_at)->format('d M Y') }}</td>
                                    <td>{{ $p->student->full_name ?? '—' }}</td>
                                    <td>{{ $p->invoice->invoice_number ?? '—' }}</td>
                                    <td class="text-capitalize">{{ $p->method }}</td>
                                    <td>{{ $p->reference_number ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($p->amount, 2) }}</td>
                                    <td>{{ $p->receivedBy->full_name ?? 'System (M-Pesa)' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="failed-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="failedTable" class="table table-hover align-middle w-100">
                            <thead><tr><th>Date</th><th>Student</th><th>Phone</th><th class="text-end">Amount</th><th>Status</th><th>Reason</th></tr></thead>
                            <tbody>
                            @foreach($failedAttempts as $t)
                                <tr>
                                    <td data-order="{{ $t->created_at->format('Y-m-d H:i') }}">{{ $t->created_at->format('d M Y H:i') }}</td>
                                    <td>{{ $t->student->full_name ?? '—' }}</td>
                                    <td>{{ $t->phone_number }}</td>
                                    <td class="text-end">{{ number_format($t->amount, 2) }}</td>
                                    <td>
                                        @php $map = ['pending' => 'secondary', 'failed' => 'danger', 'cancelled' => 'warning']; @endphp
                                        <span class="badge bg-{{ $map[$t->status] ?? 'secondary' }}-subtle text-{{ $map[$t->status] ?? 'secondary' }} text-capitalize">{{ $t->status }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $t->result_description ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">@endpush
@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $('#successfulTable').DataTable({ order: [[0, 'desc']], pageLength: 25 });
        $('#failedTable').DataTable({ order: [[0, 'desc']], pageLength: 25 });
    </script>
@endpush

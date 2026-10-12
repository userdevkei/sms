@extends('guardian.layout')
@section('title', 'Statement')

@section('content')
    @php $credit = $totals['balance'] < 0; @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="g-h1">Statement</h1>
            <p class="g-sub">{{ $child->full_name }} · Full ledger of charges and payments.</p>
        </div>
        <a href="{{ route('guardian.students.statement.pdf', $child->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="g-card g-stat text-center">
                <div class="lbl">Total Charged</div>
                <div class="val">KES {{ number_format($totals['total_charged'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="g-card g-stat text-center">
                <div class="lbl">Total Paid</div>
                <div class="val text-success">KES {{ number_format($totals['total_paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="g-card g-stat text-center">
                <div class="lbl">{{ $credit ? 'Credit Balance' : 'Balance Due' }}</div>
                <div class="val {{ $totals['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                    KES {{ number_format(abs($totals['balance']), 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="g-card">
        <div class="g-card-body">
            <div class="table-responsive">
                <table id="ledgerTable" class="table table-sm align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th class="text-end">Debit (KES)</th>
                        <th class="text-end">Credit (KES)</th>
                        <th class="text-end">Balance (KES)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($ledger as $i => $row)
                        <tr class="{{ $row['type'] === 'invoice' ? 'table-light' : '' }}">
                            <td class="text-muted small">{{ $i + 1 }}</td>
                            <td class="text-nowrap">{{ $row['date'] }}</td>
                            <td class="text-nowrap">
                                <span class="badge {{ $row['type'] === 'invoice' ? 'bg-secondary' : 'bg-success' }}">
                                    {{ $row['type'] === 'invoice' ? 'INV' : 'RCT' }}
                                </span>
                                {{ $row['label'] }}
                            </td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-end text-danger">{{ $row['debit'] !== null ? number_format($row['debit'], 2) : '—' }}</td>
                            <td class="text-end text-success">{{ $row['credit'] !== null ? number_format($row['credit'], 2) : '—' }}</td>
                            <td class="text-end fw-semibold {{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="table-dark">
                        <td colspan="4" class="fw-semibold">Totals</td>
                        <td class="text-end fw-semibold">{{ number_format($totals['total_charged'], 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($totals['total_paid'], 2) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($totals['balance'], 2) }}</td>
                    </tr>
                    </tfoot>
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
        $('#ledgerTable').DataTable({
            order: [[1, 'asc']],
            pageLength: 50,
            columnDefs: [{ targets: [0, -1], orderable: false }],
        });
    </script>
@endpush

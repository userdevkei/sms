@extends('layouts.app')
@section('title', 'My Statement')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">My Statement</h1>
            <p class="text-muted mb-0">Full ledger of charges and payments on your account.</p>
        </div>
        <a href="{{ route('finance.my-statement.pdf') }}" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Total Charged</div>
                <div class="fs-5 fw-semibold">KES {{ number_format($totals['total_charged'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Total Paid</div>
                <div class="fs-5 fw-semibold text-success">KES {{ number_format($totals['total_paid'], 2) }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm text-center py-3">
                <div class="text-muted small mb-1">Balance Due</div>
                <div class="fs-5 fw-semibold {{ $totals['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                    KES {{ number_format($totals['balance'], 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="ledgerTable" class="table table-sm fs-sm align-middle w-100">
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
                                @if($row['type'] === 'invoice')
                                    <span class="badge bg-secondary">INV</span>
                                @else
                                    <span class="badge bg-success">RCT</span>
                                @endif
                                {{ $row['label'] }}
                            </td>
                            <td>{{ $row['description'] }}</td>
                            <td class="text-end text-danger">
                                {{ $row['debit'] !== null ? number_format($row['debit'], 2) : '—' }}
                            </td>
                            <td class="text-end text-success">
                                {{ $row['credit'] !== null ? number_format($row['credit'], 2) : '—' }}
                            </td>
                            <td class="text-end fw-semibold {{ $row['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr class="table-dark">
                        <td colspan="4" class="fw-semibold">Totals</td>
                        <td class="text-end fw-semibold text-danger">{{ number_format($totals['total_charged'], 2) }}</td>
                        <td class="text-end fw-semibold text-success">{{ number_format($totals['total_paid'], 2) }}</td>
                        <td class="text-end fw-semibold {{ $totals['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($totals['balance'], 2) }}
                        </td>
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

@extends('layouts.app')
@section('title', 'Invoices')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Invoices</h1>
            <p class="text-muted mb-0">Term invoices across all students.</p>
        </div>
        @can('invoices.create')
            <a href="{{ route('finance.invoices.generate-form') }}" class="btn btn-sm btn-primary"><i class="bi bi-lightning-charge me-1"></i> Generate Invoices</a>
        @endcan
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="unpaid">Unpaid</option><option value="partially_paid">Partially Paid</option>
                        <option value="paid">Paid</option><option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">Grade Level</label>
                    <select id="filterGradeLevel" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($gradeLevels as $grade)<option value="{{ $grade->id }}">{{ $grade->name }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-hover align-middle table-striped w-100">
                    <thead><tr><th>#</th><th>Invoice No.</th><th>Student</th><th>Admission No.</th><th>Term</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
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
        const table = $('#invoicesTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: @json(route('finance.invoices.data')), data: d => {
                    d.filter_status = $('#filterStatus').val();
                    d.filter_grade_level = $('#filterGradeLevel').val();
                }},
            order: [[0, 'desc']],
            pageLength: 50,
            orderable: true,
            columns: [
                { data: 'sn', orderable: false },
                { data: 'invoice_number', render: (v, t, r) => `<a href="${r.show_url}">${v}</a>` },
                { data: 'student' }, { data: 'userID' }, { data: 'term' },
                { data: 'total' }, { data: 'paid' }, { data: 'balance' },
                { data: 'status', render: s => {
                        const map = { unpaid: 'danger', partially_paid: 'warning', paid: 'success', cancelled: 'secondary' };
                        return `<span class="badge bg-${map[s]}-subtle text-${map[s]} text-capitalize">${s.replace('_',' ')}</span>`;
                    }}
            ],
            language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...' }
        });
        $('#filterStatus, #filterGradeLevel').on('change', () => table.ajax.reload());
    </script>
@endpush

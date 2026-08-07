@extends('layouts.app')
@section('title', 'Payments')

@section('content')
    <div class="mb-3">
        <h1 class="h4 mb-1">Payments</h1>
        <p class="text-muted mb-0">All recorded payments and M-Pesa attempts across all students.</p>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#successful-pane">Successful</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#failed-pane">Failed / Pending</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="successful-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Student</label>
                            <input type="text" id="sfStudent" class="form-control form-control-sm" placeholder="Name">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Student Number</label>
                            <input type="text" id="sfStudentNumber" class="form-control form-control-sm" placeholder="Student #">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Reference</label>
                            <input type="text" id="sfReference" class="form-control form-control-sm" placeholder="Ref #">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Method</label>
                            <select id="sfMethod" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">From</label>
                            <input type="date" id="sfDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">To</label>
                            <input type="date" id="sfDateTo" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-sm btn-outline-secondary" id="sfReset">
                            <i class="bi bi-x-circle me-1"></i> Reset filters
                        </button>
                        <a href="#" id="sfExport" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Export CSV
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table id="successfulTable" class="table table-hover align-middle w-100 fs-sm table-striped">
                            <thead>
                            <tr>
                                <th nowrap>#</th><th nowrap>Student Number</th><th nowrap>Student</th>
                                <th nowrap>Method</th><th nowrap>Reference #</th><th nowrap class="text-end">Amount</th> <th nowrap>Date</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="failed-pane">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Student</label>
                            <input type="text" id="ffStudent" class="form-control form-control-sm" placeholder="Name">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Phone</label>
                            <input type="text" id="ffPhone" class="form-control form-control-sm" placeholder="07...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Reference</label>
                            <input type="text" id="ffReference" class="form-control form-control-sm" placeholder="Checkout ID">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Status</label>
                            <select id="ffStatus" class="form-select form-select-sm">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">From</label>
                            <input type="date" id="ffDateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small mb-1">To</label>
                            <input type="date" id="ffDateTo" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-sm btn-outline-secondary" id="ffReset">
                            <i class="bi bi-x-circle me-1"></i> Reset filters
                        </button>
                        <a href="#" id="ffExport" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i> Export CSV
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table id="failedTable" class="table table-hover align-middle w-100 fs-sm table-striped">
                            <thead>
                            <tr>
                                <th nowrap>#</th>
                                <th nowrap>Date</th>
                                <th nowrap>Student Number</th>
                                <th nowrap>Student Name</th>
                                <th nowrap>Phone</th>
                                <th nowrap class="text-end">Amount</th>
                                <th nowrap>Status</th>
                                <th nowrap>Reason</th>
                                <th nowrap class="text-end">Action</th>
                            </tr>
                            </thead>
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
        const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const statusBadgeMap = { pending: 'secondary', failed: 'danger', cancelled: 'warning' };

        // ── Successful table ──
        function successfulFilterParams() {
            return {
                filter_student:    $('#sfStudent').val(),
                filter_student_number: $('#sfStudentNumber').val(),
                filter_reference:  $('#sfReference').val(),
                filter_method:     $('#sfMethod').val(),
                filter_date_from:  $('#sfDateFrom').val(),
                filter_date_to:    $('#sfDateTo').val(),
            };
        }

        const successfulTable = $('#successfulTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: @json(route('finance.payments.data')),
                type: 'GET',
                data: d => Object.assign(d, successfulFilterParams()),
            },
            order: [[1, 'desc']],
            pageLength: 25,
            columns: [
                { data: 'sn', orderable: false },
                { data: 'student_number' },
                { data: 'student' },
                { data: 'method', render: d => `<span class="text-capitalize">${d}</span>` },
                { data: 'reference' },
                { data: 'amount', className: 'text-end' },
                { data: 'paid_on' },
            ],
        });

        let sfDebounce;
        $('#sfStudent, #sfPhone, #sfReference').on('input', () => {
            clearTimeout(sfDebounce);
            sfDebounce = setTimeout(() => successfulTable.ajax.reload(), 400);
        });
        $('#sfMethod, #sfDateFrom, #sfDateTo').on('change', () => successfulTable.ajax.reload());

        $('#sfReset').on('click', () => {
            $('#sfStudent, #sfPhone, #sfReference, #sfDateFrom, #sfDateTo').val('');
            $('#sfMethod').val('');
            successfulTable.ajax.reload();
        });

        function updateExportLink(anchorEl, baseUrl, params) {
            const query = new URLSearchParams(params).toString();
            anchorEl.attr('href', `${baseUrl}?${query}`);
        }

        $('#sfExport').on('click', function () {
            updateExportLink($(this), @json(route('finance.payments.export')), successfulFilterParams());
        });

        // ── Failed / Pending table ──
        let failedTable = null;

        function failedFilterParams() {
            return {
                filter_student:    $('#ffStudent').val(),
                filter_phone:      $('#ffPhone').val(),
                filter_reference:  $('#ffReference').val(),
                filter_status:     $('#ffStatus').val(),
                filter_date_from:  $('#ffDateFrom').val(),
                filter_date_to:    $('#ffDateTo').val(),
            };
        }

        function attachValidateHandlers() {
            document.querySelectorAll('.validate-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    const url = this.dataset.url;
                    const originalHtml = this.innerHTML;

                    this.disabled = true;
                    this.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking…`;

                    fetch(url, {
                        method: 'GET',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    })
                        .then(r => r.json())
                        .then(() => {
                            failedTable.ajax.reload(null, false);
                        })
                        .catch(() => {
                            this.disabled = false;
                            this.innerHTML = originalHtml;
                        });
                });
            });
        }

        function initFailedTable() {
            if (failedTable) return;

            failedTable = $('#failedTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: @json(route('finance.payments.failed-data')),
                    type: 'GET',
                    data: d => Object.assign(d, failedFilterParams()),
                },
                order: [[1, 'desc']],
                pageLength: 25,
                columns: [
                            { data: 'sn', orderable: false },
                            { data: 'date' },
                            { data: 'student_number' },
                            { data: 'student' },
                            { data: 'phone' },
                            { data: 'amount', className: 'text-end' },
                            {
                                data: 'status',
                                render: d => {
                                    const color = statusBadgeMap[d] ?? 'secondary';
                                    return `<span class="badge bg-${color}-subtle text-${color} text-capitalize">${d}</span>`;
                                },
                            },
                            { data: 'reason' },
                            {
                                data: 'id',
                                orderable: false,
                                className: 'text-end',
                                render: (id, type, row) => {
                                    if (row.status !== 'pending') return '';
                                    const url = @json(url('finance/payments')) + '/' + id + '/validate';
                                    return `<button class="btn btn-sm btn-outline-success validate-btn" data-id="${id}" data-url="${url}">
                                                <i class="bi bi-check2-circle"></i> Validate
                                            </button>`;
                                },
                            },
                        ],
                drawCallback: attachValidateHandlers,
            });
        }

        let ffDebounce;
        $('#ffStudent, #ffPhone, #ffReference').on('input', () => {
            clearTimeout(ffDebounce);
            ffDebounce = setTimeout(() => failedTable?.ajax.reload(), 400);
        });
        $('#ffStatus, #ffDateFrom, #ffDateTo').on('change', () => failedTable?.ajax.reload());

        $('#ffReset').on('click', () => {
            $('#ffStudent, #ffPhone, #ffReference, #ffDateFrom, #ffDateTo').val('');
            $('#ffStatus').val('');
            failedTable?.ajax.reload();
        });

        $('#ffExport').on('click', function () {
            updateExportLink($(this), @json(route('finance.payments.failed-export')), failedFilterParams());
        });

        document.querySelector('[data-bs-target="#failed-pane"]').addEventListener('shown.bs.tab', function () {
            initFailedTable();
        });
    </script>
@endpush
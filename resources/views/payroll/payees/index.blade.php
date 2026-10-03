@extends('layouts.app') {{-- adjust to your admin layout --}}
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Staff Payees</h4>
            <div class="d-flex gap-2">
                <a href="{{ route('payroll.schedules.index') }}" class="btn btn-sm btn-outline-secondary">Payment Schedules</a>
                <a href="{{ route('payroll.payees.import') }}" class="btn btn-sm btn-outline-primary">Import from Users</a>
                <a href="{{ route('payroll.payees.create') }}" class="btn btn-sm btn-primary">Add Staff</a>
            </div>
        </div>
        <div class="card mb-3"><div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Status</label>
                        <select class="form-select form-select-sm" id="fStatus">
                            <option value="">All</option><option value="active">Active</option><option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Source</label>
                        <select class="form-select form-select-sm" id="fSource">
                            <option value="">All</option><option value="user">System user</option><option value="standalone">Standalone</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Type</label>
                        <select class="form-select form-select-sm" id="fType">
                            <option value="">All</option><option value="permanent">Permanent</option><option value="contract">Contract</option><option value="casual">Casual</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Pay via</label>
                        <select class="form-select form-select-sm" id="fMethod">
                            <option value="">All</option><option value="bank">Bank</option><option value="mpesa">M-Pesa</option><option value="cash">Cash</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">KRA PIN</label>
                        <select class="form-select form-select-sm" id="fKra">
                            <option value="">All</option><option value="provided">Provided</option><option value="missing">Missing</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="resetFilters">Reset</button>
                        <button type="button" class="btn btn-sm btn-success flex-fill" id="exportExcel">Export Excel</button>
                    </div>
                </div>
            </div></div>

        <div class="card"><div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped fs-sm table-hover align-middle w-100" id="payeesTable">
                        <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>Staff No</th>
                            <th>Name</th>
                            <th>Source</th>
                            <th>Type</th>
                            <th>KRA PIN</th>
                            <th class="text-end">Basic</th>
                            <th>Pay via</th>
                            <th>Status</th>
                            <th style="width:120px"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($payees as $p)
                            <tr data-status="{{ $p->is_active ? 'active' : 'inactive' }}"
                                data-source="{{ $p->user_id ? 'user' : 'standalone' }}"
                                data-type="{{ $p->employment_type }}"
                                data-method="{{ $p->payment_method }}"
                                data-kra="{{ filled($p->kra_pin) ? 'provided' : 'missing' }}">
                                <td></td>
                                <td>{{ $p->staff_no }}</td>
                                <td>{{ $p->full_name }}<div class="small text-muted">{{ $p->designation }}</div></td>
                                <td>{!! $p->user_id ? '<span class="badge bg-primary">System user</span>' : '<span class="badge bg-light text-dark border">Standalone</span>' !!}</td>
                                <td>{{ ucfirst($p->employment_type) }}</td>
                                <td>{!! $p->kra_pin ?: '<span class="badge bg-warning text-dark">Missing</span>' !!}</td>
                                <td class="text-end" data-order="{{ $p->basic_salary }}">{{ number_format($p->basic_salary, 2) }}</td>
                                <td>{{ strtoupper($p->payment_method) }}</td>
                                <td>{!! $p->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' !!}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('payroll.payees.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ route('payroll.payees.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Remove this payee? Past schedules are kept.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const DT_VER = '1.13.8';
            const EXPORT_URL = @json(route('payroll.payees.export'));
            const FILTERS = { status: '#fStatus', source: '#fSource', type: '#fType', method: '#fMethod', kra: '#fKra' };

            function loadScript(src) {
                return new Promise((resolve, reject) => {
                    const s = document.createElement('script');
                    s.src = src; s.onload = resolve; s.onerror = reject;
                    document.head.appendChild(s);
                });
            }

            async function ensureDataTables() {
                if ($.fn.DataTable) return;
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = `https://cdn.datatables.net/${DT_VER}/css/dataTables.bootstrap5.min.css`;
                document.head.appendChild(css);
                await loadScript(`https://cdn.datatables.net/${DT_VER}/js/jquery.dataTables.min.js`);
                await loadScript(`https://cdn.datatables.net/${DT_VER}/js/dataTables.bootstrap5.min.js`);
            }

            $(async function () {
                const $tbl = $('#payeesTable');
                if (!$tbl.length) return;

                await ensureDataTables();

                // dropdown filters read data-* attributes on each <tr>
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    if (settings.nTable.id !== 'payeesTable') return true;
                    const ds = settings.aoData[dataIndex].nTr.dataset;
                    for (const key in FILTERS) {
                        const v = $(FILTERS[key]).val();
                        if (v && ds[key] !== v) return false;
                    }
                    return true;
                });

                const table = $tbl.DataTable({
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[2, 'asc']],
                    columnDefs: [{ orderable: false, searchable: false, targets: [0, 9] }],
                    language: {
                        search: 'Search:',
                        zeroRecords: 'No matching staff',
                        emptyTable: 'No staff payees yet.',
                        info: 'Showing _START_ to _END_ of _TOTAL_ staff',
                        infoFiltered: '(filtered from _MAX_)'
                    }
                });

                // keep # continuous across sort / search / paging / filters
                table.on('draw.dt', function () {
                    table.column(0, { search: 'applied', order: 'applied' }).nodes()
                        .each((cell, i) => { cell.innerHTML = i + 1; });
                }).draw();

                $(Object.values(FILTERS).join(',')).on('change', () => table.draw());

                $('#resetFilters').on('click', function () {
                    $(Object.values(FILTERS).join(',')).val('');
                    table.search('').draw();
                });

                // export honours the active dropdown filters + search box
                $('#exportExcel').on('click', function () {
                    const params = new URLSearchParams();
                    for (const key in FILTERS) {
                        const v = $(FILTERS[key]).val();
                        if (v) params.set(key, v);
                    }
                    const q = table.search();
                    if (q) params.set('q', q);
                    window.location.href = EXPORT_URL + (params.toString() ? '?' + params.toString() : '');
                });
            });
        })();
    </script>
@endpush

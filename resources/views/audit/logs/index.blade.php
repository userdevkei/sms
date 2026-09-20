@extends('layouts.app')
@section('title', 'Audit Log')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .filter-toolbar {
            display: flex; align-items: flex-end; gap: 1.5rem; flex-wrap: wrap;
            background: #fff; border: 1px solid #E4E1E1; border-left: 3px solid #122744;
            padding: 1rem 1.25rem; margin-bottom: 1.25rem;
        }
        .filter-toolbar .field { min-width: 190px; }
        .filter-toolbar .field label {
            display: flex; align-items: center; gap: 0.35rem;
            font-size: 0.72rem; color: #7A8391; margin-bottom: 0.3rem;
        }
        .filter-toolbar input[type="date"] {
            border: none; border-bottom: 1px solid #C9C5C0; border-radius: 0;
            padding: 0.2rem 0; font-size: 0.9rem; background: transparent; width: 100%;
        }
        .filter-toolbar input[type="date"]:focus { outline: none; border-bottom-color: #0F5C4A; box-shadow: none; }
        .filter-toolbar .date-range { display: flex; align-items: center; gap: 0.4rem; min-width: 280px; }
        .filter-toolbar .date-range span { color: #7A8391; font-size: 0.8rem; }
        .filter-toolbar .clear-filters {
            font-size: 0.8rem; color: #0F5C4A; text-decoration: none;
            border-bottom: 1px solid transparent; margin-left: auto;
        }
        .filter-toolbar .clear-filters:hover { border-bottom-color: #0F5C4A; }
        .filter-toolbar .select2-container { width: 100% !important; }
        .filter-toolbar .select2-container .select2-selection--single {
            border: none; border-bottom: 1px solid #C9C5C0; border-radius: 0;
            background: transparent; height: auto; padding: 0.2rem 1.5rem 0.25rem 0;
        }
        .filter-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding: 0; font-size: 0.9rem; color: #1C2430; line-height: 1.4;
        }
        .filter-toolbar .select2-container--default .select2-selection--single .select2-selection__arrow { height: 100%; right: 0; }
        .filter-toolbar .select2-container--default.select2-container--focus .select2-selection--single,
        .filter-toolbar .select2-container--default.select2-container--open .select2-selection--single { border-bottom-color: #0F5C4A; }

        .action-badge {
            display: inline-block; padding: 0.15rem 0.55rem; font-size: 0.75rem;
            border-radius: 3px; text-transform: capitalize;
        }
        .action-badge.created  { background: #DCF3E8; color: #0F5C4A; }
        .action-badge.updated  { background: #FDF1DA; color: #8A5A00; }
        .action-badge.deleted  { background: #FBE2E1; color: #B3261E; }
        .action-badge.default  { background: #E9EAEC; color: #4B5563; }

        .diff-table { width: 100%; font-size: 0.85rem; border-collapse: collapse; }
        .diff-table th { text-align: left; font-size: 0.72rem; color: #7A8391; padding-bottom: 0.4rem; border-bottom: 1px solid #122744; }
        .diff-table td { padding: 0.4rem 0.5rem 0.4rem 0; border-bottom: 1px solid #EAE7DD; vertical-align: top; word-break: break-word; }
        .diff-table td.changed { background: #FEF6E7; }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Audit Log</h1>
        <a href="#" id="exportExcel" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </a>
    </div>

    <div class="filter-toolbar">
        <div class="field">
            <label><i class="bi bi-person"></i> User</label>
            <select id="filterUser" class="form-select form-select-sm">
                <option value="">All Users</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ trim($u->first_name.' '.$u->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label><i class="bi bi-lightning"></i> Action</label>
            <select id="filterAction" class="form-select form-select-sm">
                <option value="">All Actions</option>
                @foreach($actions as $a)
                    <option value="{{ $a }}">{{ ucfirst($a) }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label><i class="bi bi-box"></i> Model</label>
            <select id="filterModelType" class="form-select form-select-sm">
                <option value="">All Models</option>
                @foreach($modelTypes as $fqcn => $label)
                    <option value="{{ $fqcn }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field date-range">
            <div style="flex:1;">
                <label><i class="bi bi-calendar-event"></i> From</label>
                <input type="date" id="filterDateFrom">
            </div>
            <span class="mb-1">–</span>
            <div style="flex:1;">
                <label><i class="bi bi-calendar-event"></i> To</label>
                <input type="date" id="filterDateTo">
            </div>
        </div>
        <a href="#" id="clearFilters" class="clear-filters">Clear filters</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle fs-sm table-striped w-100" id="logsTable" style="width:100%">
                    <thead>
                    <tr>
                        <th>#</th><th>Date/Time</th><th>User</th><th>Action</th>
                        <th>Model</th><th>Model ID</th><th>IP Address</th><th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="logDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Entry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="logDetailsBody">
                    <div class="text-center text-muted py-4">Loading…</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const exportBaseUrl = '{{ route("logs.export") }}';

            $('#filterUser, #filterAction, #filterModelType').select2({ width: '100%', minimumResultsForSearch: 0 });

            function currentFilterParams() {
                return {
                    filter_user: $('#filterUser').val() || '',
                    filter_action: $('#filterAction').val() || '',
                    filter_model_type: $('#filterModelType').val() || '',
                    filter_date_from: $('#filterDateFrom').val() || '',
                    filter_date_to: $('#filterDateTo').val() || '',
                };
            }

            function updateExportLink() {
                const params = new URLSearchParams(currentFilterParams());
                document.getElementById('exportExcel').href = exportBaseUrl + '?' + params.toString();
            }

            function actionBadgeClass(action) {
                const a = action.toLowerCase();
                if (a.includes('creat')) return 'created';
                if (a.includes('updat')) return 'updated';
                if (a.includes('delet')) return 'deleted';
                return 'default';
            }

            const table = $('#logsTable').DataTable({
                pageLength: 50,
                processing: true,
                serverSide: true,
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                language: { search: '', searchPlaceholder: 'Search...', lengthMenu: 'Show _MENU_' },
                ajax: {
                    url: '{{ route("logs.data") }}',
                    data: function (d) { Object.assign(d, currentFilterParams()); },
                },
                order: [[1, 'desc']],
                columns: [
                    { data: null, orderable: false, searchable: false, render: (d, t, r, meta) => meta.settings._iDisplayStart + meta.row + 1 },
                    { data: 'date' },
                    { data: 'user' },
                    { data: 'action', render: a => `<span class="action-badge ${actionBadgeClass(a)}">${a}</span>` },
                    { data: 'model' },
                    { data: 'model_id' },
                    { data: 'ip_address' },
                    {
                        data: null, orderable: false, searchable: false,
                        render: row => `<button type="button" class="btn btn-sm btn-outline-secondary btn-view-log" data-url="${row.details_url}"><i class="bi bi-eye"></i></button>`,
                    },
                ],
            });

            updateExportLink();

            $('#logsTable tbody').on('click', '.btn-view-log', function () {
                const url = this.dataset.url;
                const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
                const body = document.getElementById('logDetailsBody');
                body.innerHTML = '<div class="text-center text-muted py-4">Loading…</div>';
                modal.show();

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        const oldVals = data.old_values || {};
                        const newVals = data.new_values || {};
                        const keys = [...new Set([...Object.keys(oldVals), ...Object.keys(newVals)])];

                        let rowsHtml = keys.map(key => {
                            const oldVal = oldVals[key] !== undefined ? JSON.stringify(oldVals[key]) : '—';
                            const newVal = newVals[key] !== undefined ? JSON.stringify(newVals[key]) : '—';
                            const changed = oldVal !== newVal;
                            return `<tr>
                                <td>${key}</td>
                                <td class="${changed ? 'changed' : ''}">${oldVal}</td>
                                <td class="${changed ? 'changed' : ''}">${newVal}</td>
                            </tr>`;
                        }).join('');

                        if (!keys.length) {
                            rowsHtml = `<tr><td colspan="3" class="text-center text-muted py-3">No field-level changes recorded</td></tr>`;
                        }

                        body.innerHTML = `
                            <div class="row g-2 mb-3 small">
                                <div class="col-6"><strong>Date/Time:</strong> ${data.date}</div>
                                <div class="col-6"><strong>User:</strong> ${data.user}</div>
                                <div class="col-6"><strong>Action:</strong> ${data.action}</div>
                                <div class="col-6"><strong>Model:</strong> ${data.model || '—'} ${data.model_id ? '#' + data.model_id : ''}</div>
                                <div class="col-6"><strong>IP Address:</strong> ${data.ip_address || '—'}</div>
                                <div class="col-12"><strong>User Agent:</strong> <span class="text-muted">${data.user_agent || '—'}</span></div>
                            </div>
                            <table class="diff-table">
                                <thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        `;
                    })
                    .catch(() => {
                        body.innerHTML = '<div class="text-center text-danger py-4">Failed to load details.</div>';
                    });
            });

            $('#filterUser, #filterAction, #filterModelType').on('change', () => { table.draw(); updateExportLink(); });
            $('#filterDateFrom, #filterDateTo').on('change', () => { table.draw(); updateExportLink(); });

            document.getElementById('clearFilters').addEventListener('click', function (e) {
                e.preventDefault();
                $('#filterUser').val('').trigger('change.select2').trigger('change');
                $('#filterAction').val('').trigger('change.select2').trigger('change');
                $('#filterModelType').val('').trigger('change.select2').trigger('change');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                table.draw();
                updateExportLink();
            });
        });
    </script>
@endpush

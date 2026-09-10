@extends('layouts.app')
@section('title', 'Expenses')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .filter-toolbar {
            display: flex;
            align-items: flex-end;
            gap: 1.5rem;
            flex-wrap: wrap;
            background: #fff;
            border: 1px solid #E4E1E1;
            border-left: 3px solid #122744;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }
        .filter-toolbar .field { min-width: 190px; }
        .filter-toolbar .field label {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.72rem;
            color: #7A8391;
            margin-bottom: 0.3rem;
        }
        .filter-toolbar input[type="date"] {
            border: none;
            border-bottom: 1px solid #C9C5C0;
            border-radius: 0;
            padding: 0.2rem 0;
            font-size: 0.9rem;
            background: transparent;
            width: 100%;
        }
        .filter-toolbar input[type="date"]:focus { outline: none; border-bottom-color: #0F5C4A; box-shadow: none; }
        .filter-toolbar .date-range { display: flex; align-items: center; gap: 0.4rem; min-width: 280px; }
        .filter-toolbar .date-range span { color: #7A8391; font-size: 0.8rem; }
        .filter-toolbar .clear-filters {
            font-size: 0.8rem;
            color: #0F5C4A;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            margin-left: auto;
        }
        .filter-toolbar .clear-filters:hover { border-bottom-color: #0F5C4A; }

        .filter-toolbar .select2-container { width: 100% !important; }
        .filter-toolbar .select2-container .select2-selection--single {
            border: none;
            border-bottom: 1px solid #C9C5C0;
            border-radius: 0;
            background: transparent;
            height: auto;
            padding: 0.2rem 1.5rem 0.25rem 0;
        }
        .filter-toolbar .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding: 0;
            font-size: 0.9rem;
            color: #1C2430;
            line-height: 1.4;
        }
        .filter-toolbar .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 0;
        }
        .filter-toolbar .select2-container--default.select2-container--focus .select2-selection--single,
        .filter-toolbar .select2-container--default.select2-container--open .select2-selection--single {
            border-bottom-color: #0F5C4A;
        }
        .filter-toolbar .select2-dropdown {
            border-color: #C9C5C0;
            font-size: 0.9rem;
        }
        .filter-toolbar .select2-search--dropdown .select2-search__field {
            border: 1px solid #E4E1E1;
            padding: 0.35rem 0.5rem;
        }
        .filter-toolbar .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #122744;
        }
    </style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Expenses</h1>
        <div class="d-flex gap-2">
            <a href="#" id="exportExcel" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
            @can('expenses.manage')
                <a href="{{ route('accounting.expense.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Record Expense
                </a>
            @endcan
        </div>
    </div>

    <div class="filter-toolbar">
        <div class="field">
            <label><i class="bi bi-tag"></i> Category</label>
            <select id="filterCategory" class="form-select form-select-sm">
                <option value="">All Categories</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label><i class="bi bi-calendar3"></i> Academic Year</label>
            <select id="filterYear" class="form-select form-select-sm">
                <option value="">All Years</option>
                @foreach($academicYears as $y)
                    <option value="{{ $y }}" @selected($y == $academicYear)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label><i class="bi bi-clock-history"></i> Term</label>
            <select id="filterTerm" class="form-select form-select-sm">
                <option value="">All Terms</option>
                @foreach([1,2,3] as $t) <option value="{{ $t }}">Term {{ $t }}</option> @endforeach
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
                <table class="table table-sm align-middle fs-sm table-striped w-100" id="expensesTable" style="width:100%">
                    <thead>
                    <tr>
                        <th>#</th><th>Date</th><th>Reference</th><th>Category</th><th>Items</th>
                        <th>Amount</th><th>Vendor</th><th>Term</th><th>Recorded By</th><th></th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
            const canDelete = @json($canDelete);
            const exportBaseUrl = '{{ route("accounting.expense.export") }}';

            $('#filterCategory, #filterYear, #filterTerm').select2({
                width: '100%',
                minimumResultsForSearch: 0,
            });

            function currentFilterParams() {
                return {
                    filter_category: $('#filterCategory').val() || '',
                    filter_year: $('#filterYear').val() || '',
                    filter_term: $('#filterTerm').val() || '',
                    filter_date_from: $('#filterDateFrom').val() || '',
                    filter_date_to: $('#filterDateTo').val() || '',
                };
            }

            function updateExportLink() {
                const params = new URLSearchParams(currentFilterParams());
                document.getElementById('exportExcel').href = exportBaseUrl + '?' + params.toString();
            }

            const table = $('#expensesTable').DataTable({
                processing: true,
                serverSide: true,
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                language: {
                    search: '',
                    searchPlaceholder: 'Search...',
                    lengthMenu: 'Show _MENU_',
                },
                ajax: {
                    url: '{{ route("accounting.expense.data") }}',
                    data: function (d) {
                        Object.assign(d, currentFilterParams());
                    },
                },
                columns: [
                    {
                        data: null, orderable: false, searchable: false,
                        render: (data, type, row, meta) => meta.settings._iDisplayStart + meta.row + 1,
                    },
                    { data: 'date' },
                    { data: 'reference' },
                    { data: 'category' },
                    { data: 'items_count' },
                    { data: 'amount' },
                    { data: 'vendor' },
                    { data: 'term' },
                    { data: 'recorded_by' },
                    {
                        data: null, orderable: false, searchable: false,
                        createdCell: function (td) { td.style.whiteSpace = 'nowrap'; },
                        render: function (data, type, row) {
                            let buttons = `<div class="d-inline-flex flex-nowrap align-items-center gap-1">`;
                            buttons += `<a href="${row.receipt_url}" target="_blank" class="btn btn-sm btn-outline-primary" title="Print voucher"><i class="bi bi-printer"></i></a>`;
                            buttons += `<a href="${row.edit_url}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>`;
                            if (canDelete) {
                                buttons += `<button type="button" class="btn btn-sm btn-outline-danger btn-delete-expense" data-config='${JSON.stringify({id: row.id, url: row.delete_url})}'><i class="bi bi-trash"></i></button>`;
                            }
                            buttons += `</div>`;
                            return buttons;
                        },
                    },
                ],
            });

            updateExportLink();

            $('#expensesTable tbody').on('click', '.btn-delete-expense', function () {
                const cfg = JSON.parse(this.dataset.config);
                if (!confirm('Delete this expense transaction? This cannot be undone.')) return;

                fetch(cfg.url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) table.draw();
                        else alert(res.message);
                    });
            });

            $('#filterCategory, #filterYear, #filterTerm').on('change', () => { table.draw(); updateExportLink(); });
            $('#filterDateFrom, #filterDateTo').on('change', () => { table.draw(); updateExportLink(); });

            document.getElementById('clearFilters').addEventListener('click', function (e) {
                e.preventDefault();
                $('#filterCategory').val('').trigger('change.select2').trigger('change');
                $('#filterYear').val('').trigger('change.select2').trigger('change');
                $('#filterTerm').val('').trigger('change.select2').trigger('change');
                $('#filterDateFrom').val('');
                $('#filterDateTo').val('');
                table.draw();
                updateExportLink();
            });
        });
    </script>
@endpush

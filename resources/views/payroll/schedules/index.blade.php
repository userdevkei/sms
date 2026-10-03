@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #schedulesTable thead th { white-space: nowrap; }
        #schedulesTable tfoot th { background: #f8fafc; border-top: 2px solid #1e3a5f; white-space: nowrap; }

        .filter-bar .form-label,
        .generate-bar .form-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .5px; color: #64748b; margin-bottom: 2px; }

        /* Page-size selector: no more overlapping arrow, sits neatly top-left beside the buttons */
        .dataTables_wrapper .dataTables_length label {
            display: flex; align-items: center; gap: .5rem; margin: 0;
            font-size: .8rem; color: #64748b; white-space: nowrap;
        }
        .dataTables_wrapper .dataTables_length select {
            width: auto !important; min-width: 4.75rem; padding-right: 2rem !important;
        }
        .dataTables_wrapper .dataTables_filter label { margin: 0; }
        .dataTables_wrapper .dataTables_filter input { margin-left: 0 !important; width: 100%; }
        .dataTables_wrapper .dataTables_info { padding-top: 0 !important; font-size: .8rem; color: #64748b; }
        .dataTables_wrapper .dataTables_paginate .pagination { margin: 0; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Staff Payment Schedules</h4>
            <a href="{{ route('payroll.payees.index') }}" class="btn btn-sm btn-outline-secondary">Manage Staff Payees</a>
        </div>

        {{-- Generate --}}
        <div class="card mb-3"><div class="card-body generate-bar">
                <form method="POST" action="{{ route('payroll.schedules.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Month</label>
                        <input type="month" name="period" value="{{ now()->format('Y-m') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Title (optional)</label>
                        <input name="title" class="form-control form-control-sm" placeholder="Staff Payment Schedule – October 2026">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-sm btn-primary w-100">Generate</button>
                    </div>
                </form>
            </div></div>

        {{-- Filters --}}
        <div class="card mb-3"><div class="card-body filter-bar">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" for="fStatus">Status</label>
                        <select id="fStatus" class="form-select form-select-sm">
                            <option value="">All statuses</option>
                            <option value="Draft">Draft</option>
                            <option value="Approved">Approved</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fFrom">Period from</label>
                        <input type="month" id="fFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fTo">Period to</label>
                        <input type="month" id="fTo" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fNetMin">Net pay min</label>
                        <input type="number" id="fNetMin" class="form-control form-control-sm" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="fNetMax">Net pay max</label>
                        <input type="number" id="fNetMax" class="form-control form-control-sm" min="0" step="0.01" placeholder="Any">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="button" id="fThisYear" class="btn btn-sm btn-outline-primary flex-fill">This year</button>
                        <button type="button" id="fReset" class="btn btn-sm btn-outline-secondary flex-fill">Reset</button>
                    </div>
                </div>
            </div></div>

        {{-- Table --}}
        <div class="card"><div class="card-body">
                <table id="schedulesTable" class="table table-hover align-middle w-100">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Period</th>
                        <th>Title</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">PAYE</th>
                        <th class="text-end">Net</th>
                        <th>Status</th>
                        <th class="text-end no-colvis">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($schedules as $s)
                        @php $badge = ['draft' => 'secondary', 'approved' => 'info', 'paid' => 'success'][$s->status] ?? 'secondary'; @endphp
                        <tr data-id="{{ $s->id }}" data-period="{{ $s->period->format('Y-m') }}" data-net="{{ (float) $s->total_net }}">
                            <td>{{ $loop->iteration }}</td>
                            <td data-order="{{ $s->period->format('Y-m') }}">{{ $s->period->format('M Y') }}</td>
                            <td>{{ $s->title }}</td>
                            <td class="text-end" data-order="{{ (float) $s->total_gross }}">{{ number_format($s->total_gross, 2) }}</td>
                            <td class="text-end" data-order="{{ (float) $s->total_paye }}">{{ number_format($s->total_paye, 2) }}</td>
                            <td class="text-end" data-order="{{ (float) $s->total_net }}">{{ number_format($s->total_net, 2) }}</td>
                            <td><span class="badge bg-{{ $badge }}">{{ ucfirst($s->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('payroll.schedules.show', $s) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th></th>
                        <th class="text-end">Totals (filtered)</th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th class="text-end"></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </tfoot>
                </table>
            </div></div>
    </div>
@endsection

@push('scripts')
    {{-- jQuery: only loaded if your layout does not already provide it --}}
    <script>window.jQuery || document.write('<script src="https://code.jquery.com/jquery-3.7.1.min.js"><\/script>')</script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
        $(function () {
            const num = v => parseFloat(String(v).replace(/<[^>]*>/g, '').replace(/,/g, '')) || 0;
            const fmt = n => n.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const exportUrl = @json(route('payroll.schedules.export'));

            // Small buttons; the per-button classes below only set the colour
            $.fn.dataTable.Buttons.defaults.dom.button.className = 'btn btn-sm';

            const table = $('#schedulesTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [[0, 'desc']],
                columnDefs: [
                    { targets: -1, orderable: false, searchable: false },
                    { targets: [2, 3, 4], className: 'text-end' }
                ],
                dom: "<'row mb-3 align-items-center'<'col-md-8 d-flex flex-wrap align-items-center gap-3'lB><'col-md-4'f>>" +
                    "<'row'<'col-12'tr>>" +
                    "<'row mt-3 align-items-center'<'col-md-6'i><'col-md-6 d-flex justify-content-md-end'p>>",
                buttons: [
                    {
                        text: 'Export Excel',
                        className: 'btn-success',
                        action: function () {
                            // Export exactly the rows currently visible after all filters/search
                            const ids = table.rows({ search: 'applied' }).nodes().toArray().map(tr => tr.dataset.id);
                            if (!ids.length) {
                                alert('Nothing to export. Adjust your filters first.');
                                return;
                            }
                            window.location.href = exportUrl + '?ids=' + ids.join(',');
                        }
                    },
                    { extend: 'colvis', text: 'Columns', className: 'btn-outline-primary', columns: ':not(.no-colvis)' }
                ],
                language: {
                    search: '',
                    searchPlaceholder: 'Search schedules…',
                    lengthMenu: 'Show _MENU_',
                    emptyTable: 'No schedules yet.',
                    zeroRecords: 'No schedules match your filters.'
                },
                footerCallback: function () {
                    const api = this.api();
                    [2, 3, 4].forEach(col => {
                        const total = api.column(col, { search: 'applied' }).data()
                            .reduce((sum, v) => sum + num(v), 0);
                        $(api.column(col).footer()).html(fmt(total));
                    });
                }
            });

            /* ── Custom filters (period range + net pay range) ── */
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'schedulesTable') return true;

                const tr     = settings.aoData[dataIndex].nTr;
                const period = tr.dataset.period;            // YYYY-MM
                const net    = parseFloat(tr.dataset.net);
                const from   = $('#fFrom').val();
                const to     = $('#fTo').val();
                const min    = $('#fNetMin').val();
                const max    = $('#fNetMax').val();

                if (from && period < from) return false;
                if (to && period > to) return false;
                if (min !== '' && net < parseFloat(min)) return false;
                if (max !== '' && net > parseFloat(max)) return false;
                return true;
            });

            // Status filter (exact match on column 5)
            $('#fStatus').on('change', function () {
                const v = this.value;
                table.column(5).search(v ? '^' + v + '$' : '', true, false).draw();
            });

            $('#fFrom, #fTo, #fNetMin, #fNetMax').on('input change', () => table.draw());

            $('#fThisYear').on('click', function () {
                const y = new Date().getFullYear();
                $('#fFrom').val(y + '-01');
                $('#fTo').val(y + '-12');
                table.draw();
            });

            $('#fReset').on('click', function () {
                $('#fStatus').val('');
                $('#fFrom, #fTo, #fNetMin, #fNetMax').val('');
                table.search('').columns().search('').draw();
            });
        });
    </script>
@endpush

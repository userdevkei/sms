@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Import Staff from Users</h4>
            <a href="{{ route('payroll.payees.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <div class="alert alert-info">Students and users already on the payee list are excluded. Imported staff start <strong>inactive</strong> until you set their basic salary and activate them.</div>

        @if($users->isEmpty())
            <div class="card"><div class="card-body text-center text-muted">No eligible users.</div></div>
        @else
            <form method="POST" action="{{ route('payroll.payees.import.store') }}" id="importForm">
                @csrf
                <div class="card"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div><span id="selCount" class="fw-bold">0</span> selected</div>
                            <button type="submit" class="btn btn-sm btn-primary" id="importBtn" disabled>Import selected</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle w-100" id="importTable">
                                <thead>
                                <tr>
                                    <th style="width:40px"><input type="checkbox" id="checkAll" title="Select all (filtered)"></th>
                                    <th style="width:50px">#</th>
                                    <th>Name</th>
                                    <th>User ID</th>
                                    <th>Phone</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $u)
                                    <tr>
                                        <td><input type="checkbox" class="rowCheck" value="{{ $u['id'] }}"></td>
                                        <td></td>
                                        <td>{{ $u['name'] }}</td>
                                        <td>{{ $u['staff_no'] }}</td>
                                        <td>{{ $u['phone'] }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div></div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const DT_VER = '1.13.8';

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
                const $tbl = $('#importTable');
                if (!$tbl.length) return;

                await ensureDataTables();

                const selected = new Set(); // survives paging / filtering
                let table = null;           // declared first: drawCallback fires during init

                function filteredBoxes() {
                    return table.rows({ search: 'applied' }).nodes().to$().find('.rowCheck');
                }

                function refreshUi() {
                    if (!table) return;
                    $('#selCount').text(selected.size);
                    $('#importBtn').prop('disabled', selected.size === 0);

                    const $all = filteredBoxes();
                    const checked = $all.filter((_, el) => selected.has(el.value)).length;
                    const head = document.getElementById('checkAll');
                    head.checked = $all.length > 0 && checked === $all.length;
                    head.indeterminate = checked > 0 && checked < $all.length;
                }

                function renumber() {
                    if (!table) return;
                    table.column(1, { search: 'applied', order: 'applied' }).nodes()
                        .each((cell, i) => { cell.innerHTML = i + 1; });
                }

                table = $tbl.DataTable({
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    order: [[2, 'asc']],
                    columnDefs: [{ orderable: false, searchable: false, targets: [0, 1] }],
                    language: {
                        search: 'Search:',
                        zeroRecords: 'No matching users',
                        info: 'Showing _START_ to _END_ of _TOTAL_ users',
                        infoFiltered: '(filtered from _MAX_)'
                    },
                    drawCallback: function () {
                        $tbl.find('.rowCheck').each(function () { this.checked = selected.has(this.value); });
                        renumber();
                        refreshUi();
                    }
                });

                renumber();
                refreshUi();

                // single row
                $tbl.on('change', '.rowCheck', function () {
                    this.checked ? selected.add(this.value) : selected.delete(this.value);
                    refreshUi();
                });

                // select all = every row matching the current search, across all pages
                $('#checkAll').on('change', function () {
                    const on = this.checked;
                    filteredBoxes().each(function () {
                        this.checked = on;
                        on ? selected.add(this.value) : selected.delete(this.value);
                    });
                    refreshUi();
                });

                // rows on other pages are detached from the DOM, so submit from the Set
                $('#importForm').on('submit', function () {
                    $(this).find('input[name="user_ids[]"]').remove();
                    selected.forEach(id => $('<input>', { type: 'hidden', name: 'user_ids[]', value: id }).appendTo(this));
                });
            });
        })();
    </script>
@endpush

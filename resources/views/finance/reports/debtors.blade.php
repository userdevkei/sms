{{-- Adjust the layout / section / stack names to match your app layout. --}}
@extends('layouts.app')

@section('title', 'Debtors report')

@section('content')
    <div class="container-fluid py-3" id="debtorsPage" data-config="{{ json_encode($config) }}">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-0">Debtors report</h4>
                <div class="text-muted small">Students with an outstanding balance. Results update as you change a filter, and exports use the same filters.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('finance.reports.index') }}" class="btn btn-outline-secondary btn-sm">All reports</a>
                <a id="btnExcel" href="#" target="_blank" class="btn btn-success btn-sm js-export">Export to Excel</a>
                <a id="btnPdf" href="#" target="_blank" class="btn btn-danger btn-sm js-export">Export to PDF</a>
            </div>
        </div>

        {{-- Filters --}}
        <form id="filterForm" class="card shadow-sm mb-3" onsubmit="return false">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="grade_ids">Grade</label>
                        <select id="grade_ids" multiple data-placeholder="All grades">
                            @foreach ($config['grades'] as $g)
                                <option value="{{ $g['id'] }}">{{ $g['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="stream_ids">Stream</label>
                        <select id="stream_ids" multiple data-placeholder="All streams"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="q">Student</label>
                        <input type="search" id="q" class="form-control" placeholder="Search by name or admission number" autocomplete="off">
                    </div>

                    <div class="col-lg-6">
                        <label class="form-label small fw-semibold">
                            Balance owed <span class="text-muted fw-normal">(share of fees invoiced that is still unpaid)</span>
                        </label>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary js-pct-chip" data-min="" data-max="">Any</button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-pct-chip" data-min="25" data-max="">25% or more</button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-pct-chip" data-min="50" data-max="">50% or more</button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-pct-chip" data-min="75" data-max="">75% or more</button>
                            <button type="button" class="btn btn-sm btn-outline-primary js-pct-chip" data-min="100" data-max="100">Paid nothing</button>
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">From</span>
                                    <input type="number" id="min_pct" class="form-control" min="0" max="100" step="1" placeholder="0">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">To</span>
                                    <input type="number" id="max_pct" class="form-control" min="0" max="100" step="1" placeholder="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-semibold" for="min_balance">Minimum balance ({{ $currency }})</label>
                        <input type="number" id="min_balance" class="form-control form-control-sm" min="0" step="100" placeholder="e.g. 5000">
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label class="form-label small fw-semibold">Group breakdown and exports by</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            @foreach (['none' => 'Nothing', 'grade' => 'Grade', 'stream' => 'Stream'] as $v => $label)
                                <input type="radio" class="btn-check" name="group_by" id="gb_{{ $v }}" value="{{ $v }}"
                                    @checked(request('group_by', 'none') === $v)>
                                <label class="btn btn-outline-secondary" for="gb_{{ $v }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-3 text-end">
                    <button type="button" id="btnReset" class="btn btn-link btn-sm text-decoration-none">Reset filters</button>
                </div>
            </div>
        </form>

        {{-- KPIs --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100"><div class="card-body py-2">
                        <div class="text-muted small">Debtors</div>
                        <div class="fs-4 fw-bold" id="kpiDebtors">—</div>
                    </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100"><div class="card-body py-2">
                        <div class="text-muted small">Invoiced ({{ $currency }})</div>
                        <div class="fs-4 fw-bold" id="kpiInvoiced">—</div>
                    </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100"><div class="card-body py-2">
                        <div class="text-muted small">Collected ({{ $currency }})</div>
                        <div class="fs-4 fw-bold text-success" id="kpiPaid">—</div>
                    </div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm h-100"><div class="card-body py-2">
                        <div class="text-muted small">Outstanding ({{ $currency }})</div>
                        <div class="fs-4 fw-bold text-danger" id="kpiBalance">—</div>
                        <div class="small text-muted" id="kpiPct"></div>
                    </div></div>
            </div>
        </div>

        {{-- Breakdown --}}
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold" id="breakdownTitle">Breakdown by grade</span>
                <span class="small text-muted">Select a row to filter the list to it</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th class="text-end">Debtors</th>
                        <th class="text-end">Invoiced</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                        <th style="min-width:140px">% owing</th>
                    </tr>
                    </thead>
                    <tbody id="breakdownBody"></tbody>
                </table>
            </div>
        </div>

        {{-- Debtors list --}}
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="debtorsTable" class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width:50px">#</th>
                            <th class="js-sort" data-col="1" role="button">Adm no<span class="sort-ind"></span></th>
                            <th class="js-sort" data-col="2" role="button">Student<span class="sort-ind"></span></th>
                            <th class="js-sort" data-col="3" role="button">Grade<span class="sort-ind"></span></th>
                            <th class="js-sort" data-col="4" role="button">Stream<span class="sort-ind"></span></th>
                            <th class="js-sort text-end" data-col="5" role="button">Invoiced<span class="sort-ind"></span></th>
                            <th class="js-sort text-end" data-col="6" role="button">Paid<span class="sort-ind"></span></th>
                            <th class="js-sort text-end" data-col="7" role="button">Balance<span class="sort-ind"></span></th>
                            <th class="js-sort" data-col="8" role="button" style="min-width:140px">% owing<span class="sort-ind"></span></th>
                        </tr>
                        </thead>
                        <tbody id="debtorsBody">
                        <tr><td colspan="9" class="text-center text-muted py-4">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                    <div class="d-flex align-items-center gap-2 small text-muted">
                        <span>Show</span>
                        <select id="pageSize" class="form-select form-select-sm w-auto">
                            <option>25</option><option>50</option><option>100</option>
                        </select>
                        <span id="tableInfo"></span>
                    </div>
                    <nav aria-label="Debtors pages"><ul class="pagination pagination-sm mb-0" id="pager"></ul></nav>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            const cfg = JSON.parse($('#debtorsPage').attr('data-config'));
            const money = n => Number(n).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const esc = s => $('<div>').text(s ?? '').html();

            function pctCell(p) {
                p = Number(p);
                const tone = p >= 75 ? 'bg-danger' : p >= 50 ? 'bg-warning' : p >= 25 ? 'bg-info' : 'bg-success';
                return `<div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                        <div class="progress-bar ${tone}" style="width:${Math.min(p, 100)}%"></div>
                    </div>
                    <span class="small fw-semibold">${p.toFixed(1)}%</span>
                </div>`;
            }

            /* ---------- Select2 + dependent streams ---------- */
            $('#grade_ids, #stream_ids').each(function () {
                $(this).select2({ width: '100%', placeholder: $(this).data('placeholder'), allowClear: true, closeOnSelect: false });
            });

            function syncStreams(keep) {
                keep = (keep || $('#stream_ids').val() || []).map(String);
                const grades = ($('#grade_ids').val() || []).map(String);
                const $s = $('#stream_ids').empty();

                cfg.streams
                    .filter(s => !grades.length || !s.grade_id || grades.includes(s.grade_id))
                    .forEach(s => $s.append(new Option(s.label, s.id, false, keep.includes(s.id))));

                $s.trigger('change.select2');
            }

            // Restore filters coming from the reports hub (e.g. ?min_pct=50)
            const q = new URLSearchParams(window.location.search);
            $('#grade_ids').val(cfg.initial.grade_ids).trigger('change.select2');
            syncStreams(cfg.initial.stream_ids);
            $('#min_pct').val(q.get('min_pct') ?? '');
            $('#max_pct').val(q.get('max_pct') ?? '');
            $('#min_balance').val(q.get('min_balance') ?? '');
            $('#q').val(q.get('q') ?? '');

            function filters() {
                return {
                    grade_ids: $('#grade_ids').val() || [],
                    stream_ids: $('#stream_ids').val() || [],
                    min_pct: $('#min_pct').val(),
                    max_pct: $('#max_pct').val(),
                    min_balance: $('#min_balance').val(),
                    q: $('#q').val(),
                    group_by: $('input[name=group_by]:checked').val()
                };
            }

            /* ---------- Exports use exactly the same filters ---------- */
            function syncExports() {
                const qs = $.param(filters());
                $('#btnExcel').attr('href', cfg.routes.excel + '?' + qs);
                $('#btnPdf').attr('href', cfg.routes.pdf + '?' + qs);
            }

            function syncChips() {
                const mn = $('#min_pct').val(), mx = $('#max_pct').val();
                $('.js-pct-chip').each(function () {
                    const on = $(this).attr('data-min') === mn && $(this).attr('data-max') === mx;
                    $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-primary', !on);
                });
            }

            /* ---------- KPIs + breakdown ---------- */
            let summaryReq = 0;
            function loadSummary() {
                const id = ++summaryReq;
                $.getJSON(cfg.routes.summary, filters())
                    .done(res => {
                        if (id !== summaryReq) return; // ignore out-of-order responses
                        const s = res.summary;
                        $('#kpiDebtors').text(Number(s.debtors).toLocaleString());
                        $('#kpiInvoiced').text(money(s.invoiced));
                        $('#kpiPaid').text(money(s.paid));
                        $('#kpiBalance').text(money(s.balance));
                        $('#kpiPct').text(s.pct + '% of invoiced fees');
                        $('.js-export').toggleClass('disabled', Number(s.debtors) === 0);
                        renderBreakdown(res);
                    })
                    .fail(() => {
                        if (id !== summaryReq) return;
                        $('.js-export').addClass('disabled');
                        $('#breakdownBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Could not load the summary. Check the filter values and try again.</td></tr>');
                    });
            }

            function renderBreakdown(res) {
                const byStream = res.group_by === 'stream';
                $('#breakdownTitle').text('Breakdown by ' + (byStream ? 'stream' : 'grade'));

                const html = res.breakdown.map(r => `
            <tr style="cursor:pointer" data-grade="${esc(r.grade_id)}" data-stream="${esc(byStream ? (r.stream_id ?? '') : '')}">
                <td>${esc(r.label)}</td>
                <td class="text-end">${r.debtors}</td>
                <td class="text-end">${money(r.invoiced)}</td>
                <td class="text-end">${money(r.paid)}</td>
                <td class="text-end fw-semibold">${money(r.balance)}</td>
                <td>${pctCell(r.pct)}</td>
            </tr>`).join('');

                $('#breakdownBody').html(html || '<tr><td colspan="6" class="text-center text-muted py-3">No debtors match the selected filters.</td></tr>');
            }

            $('#breakdownBody').on('click', 'tr[data-grade]', function () {
                const grade = $(this).attr('data-grade'); // attr(), not data(): jQuery would coerce numeric-looking IDs
                const stream = $(this).attr('data-stream');

                $('#grade_ids').val([grade]).trigger('change.select2');
                syncStreams(stream ? [stream] : []);
                refresh();
            });

            /* ---------- Debtors table: plain jQuery/AJAX (no DataTables dependency) ---------- */
            const state = { page: 1, size: 25, col: 7, dir: 'desc', total: 0 }; // col 7 = Balance, matches the controller's sort map

            let rowsReq = 0;
            function loadRows() {
                const id = ++rowsReq;
                $('#debtorsBody').css('opacity', 0.5);

                $.getJSON(cfg.routes.data, Object.assign(filters(), {
                    draw: id,
                    start: (state.page - 1) * state.size,
                    length: state.size,
                    order: [{ column: state.col, dir: state.dir }]
                }))
                    .done(res => {
                        if (id !== rowsReq) return; // ignore out-of-order responses
                        state.total = Number(res.recordsFiltered);

                        const pages = Math.max(1, Math.ceil(state.total / state.size));
                        if (state.page > pages) { state.page = pages; return loadRows(); }

                        renderRows(res.data);
                        renderPager();
                    })
                    .fail(() => {
                        if (id !== rowsReq) return;
                        $('#debtorsBody').css('opacity', 1).html('<tr><td colspan="9" class="text-center text-danger py-4">Could not load debtors. Check the filter values and try again.</td></tr>');
                    });
            }

            function renderRows(rows) {
                const html = rows.map(r => `
            <tr>
                <td class="text-muted">${r.no}</td>
                <td>${esc(r.admission_no)}</td>
                <td class="fw-semibold">${esc(r.student_name)}</td>
                <td>${esc(r.grade_name)}</td>
                <td>${esc(r.stream_name)}</td>
                <td class="text-end">${money(r.invoiced)}</td>
                <td class="text-end">${money(r.paid)}</td>
                <td class="text-end fw-semibold">${money(r.balance)}</td>
                <td>${pctCell(r.balance_pct)}</td>
            </tr>`).join('');

                $('#debtorsBody')
                    .html(html || '<tr><td colspan="9" class="text-center text-muted py-4">No debtors match the selected filters.</td></tr>')
                    .css('opacity', 1);
            }

            function renderPager() {
                const pages = Math.max(1, Math.ceil(state.total / state.size));
                const p = state.page;
                const from = state.total ? (p - 1) * state.size + 1 : 0;
                const to = Math.min(p * state.size, state.total);
                $('#tableInfo').text(`Showing ${from.toLocaleString()}–${to.toLocaleString()} of ${state.total.toLocaleString()}`);

                let html = `<li class="page-item ${p === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p - 1}">Previous</a></li>`;
                let prev = 0;
                for (let i = 1; i <= pages; i++) {
                    if (i !== 1 && i !== pages && Math.abs(i - p) > 2) continue;
                    if (prev && i - prev > 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    html += `<li class="page-item ${i === p ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    prev = i;
                }
                html += `<li class="page-item ${p === pages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${p + 1}">Next</a></li>`;
                $('#pager').html(html);
            }

            function markSort() {
                $('.js-sort .sort-ind').text('');
                $(`.js-sort[data-col="${state.col}"] .sort-ind`).text(state.dir === 'asc' ? ' ▲' : ' ▼');
            }

            $('#pager').on('click', 'a[data-page]', function (e) {
                e.preventDefault();
                const pg = parseInt($(this).attr('data-page'), 10);
                if (pg >= 1) { state.page = pg; loadRows(); }
            });

            $('#debtorsTable thead').on('click', '.js-sort', function () {
                const col = parseInt($(this).attr('data-col'), 10);
                state.dir = state.col === col ? (state.dir === 'asc' ? 'desc' : 'asc') : (col >= 5 ? 'desc' : 'asc');
                state.col = col;
                state.page = 1;
                markSort();
                loadRows();
            });

            $('#pageSize').on('change', function () {
                state.size = parseInt($(this).val(), 10);
                state.page = 1;
                loadRows();
            });

            /* ---------- Events ---------- */
            let timer;
            function refresh() {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    state.page = 1;
                    syncExports();
                    syncChips();
                    loadRows();
                    loadSummary();
                }, 250);
            }

            $('#grade_ids').on('change', () => { syncStreams(); refresh(); });
            $('#stream_ids, #min_pct, #max_pct, #min_balance, #q').on('input change', refresh);
            $('input[name=group_by]').on('change', refresh);

            $('.js-pct-chip').on('click', function () {
                $('#min_pct').val($(this).attr('data-min'));
                $('#max_pct').val($(this).attr('data-max'));
                refresh();
            });

            $('#btnReset').on('click', function () {
                $('#grade_ids').val(null).trigger('change.select2');
                syncStreams([]);
                $('#min_pct, #max_pct, #min_balance, #q').val('');
                $('#gb_none').prop('checked', true);
                refresh();
            });

            syncExports();
            syncChips();
            markSort();
            loadRows();
            loadSummary();
        });
    </script>
@endpush

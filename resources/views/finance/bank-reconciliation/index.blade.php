@extends('layouts.app')

@section('content')
    <style>
        .recon {
            --r-border: #e8ecf3;
            --r-muted: #64748b;
            --r-ink: #0f172a;
            --r-radius: 14px;
        }
        .recon .card { border: 1px solid var(--r-border); border-radius: var(--r-radius); box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }

        /* ---------- Overview (progress + clickable stats) ---------- */
        .recon-overview { overflow: hidden; }
        .recon-overview-top { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; padding: 20px 24px 14px; }
        .recon-rate { font-size: 1.75rem; font-weight: 700; line-height: 1; color: var(--r-ink); font-variant-numeric: tabular-nums; }
        .recon-bar { height: 8px; margin: 0 24px 20px; border-radius: 999px; background: #fde7c0; overflow: hidden; }
        .recon-bar span { display: block; height: 100%; width: 0; border-radius: 999px; background: #16a34a; transition: width .6s ease; }

        .recon-stats { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid var(--r-border); }
        .recon-stat { appearance: none; border: 0; background: transparent; font: inherit; color: inherit; text-align: left; position: relative;
            display: flex; flex-direction: column; gap: 3px; padding: 18px 24px; cursor: pointer; transition: background .15s; }
        .recon-stat + .recon-stat { border-left: 1px solid var(--r-border); }
        .recon-stat:hover, .recon-stat.is-active { background: #f8fafc; }
        .recon-stat.is-active::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: var(--accent); }
        .recon-stat:focus-visible { outline: 2px solid var(--bs-primary); outline-offset: -2px; }
        .recon-stat-label { display: flex; align-items: center; gap: 8px; font-size: .72rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: var(--r-muted); }
        .recon-stat-label i { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); display: inline-block; }
        .recon-stat-value { font-size: 1.75rem; font-weight: 700; line-height: 1.15; color: var(--r-ink); font-variant-numeric: tabular-nums; }
        .recon-stat-sub { font-size: .84rem; color: var(--r-muted); font-variant-numeric: tabular-nums; }
        @media (max-width: 767.98px) {
            .recon-stats { grid-template-columns: 1fr; }
            .recon-stat + .recon-stat { border-left: 0; border-top: 1px solid var(--r-border); }
        }

        .recon-skel { display: inline-block; width: 64px; height: 1em; border-radius: 6px; vertical-align: middle;
            background: linear-gradient(90deg, #eef0f5 25%, #f7f8fb 37%, #eef0f5 63%); background-size: 400% 100%; animation: recon-shimmer 1.3s infinite; }
        .recon-skel.wide { width: 110px; height: .8em; }
        @keyframes recon-shimmer { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }

        /* ---------- Table card ---------- */
        .recon-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; padding: 18px 20px; border-bottom: 1px solid var(--r-border); }
        .recon-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .recon-search { position: relative; }
        .recon-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .recon-search input { padding-left: 34px; min-width: 260px; }
        .recon-range { display: flex; align-items: center; gap: 6px; color: #94a3b8; }

        #reconTable { width: 100% !important; border-collapse: separate; border-spacing: 0; margin: 0 !important; }
        #reconTable thead th { background: #f8fafc; color: var(--r-muted); font-size: .7rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; padding: .8rem 1rem; border-bottom: 1px solid var(--r-border); white-space: nowrap; }
        #reconTable tbody td { padding: .9rem 1rem; border-bottom: 1px solid #f0f3f8; vertical-align: middle; }
        #reconTable tbody tr:last-child td { border-bottom: 0; }
        #reconTable tbody tr:hover td { background: #fafbff; }
        /* DataTables base CSS isn't loaded, so draw the sort arrows ourselves */
        #reconTable thead th.sorting, #reconTable thead th.sorting_asc, #reconTable thead th.sorting_desc { cursor: pointer; position: relative; padding-right: 1.7rem; }
        #reconTable thead th.sorting::after, #reconTable thead th.sorting_asc::after, #reconTable thead th.sorting_desc::after { position: absolute; right: .6rem; font-size: .75rem; }
        #reconTable thead th.sorting::after { content: '\2195'; opacity: .35; }
        #reconTable thead th.sorting_asc::after { content: '\2191'; color: var(--bs-primary); }
        #reconTable thead th.sorting_desc::after { content: '\2193'; color: var(--bs-primary); }

        .recon-ref { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; color: var(--r-ink); }
        .recon-amount { font-weight: 600; color: var(--r-ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .recon-avatar { width: 34px; height: 34px; flex: 0 0 34px; border-radius: 50%; display: grid; place-items: center; font-size: .72rem; font-weight: 700; background: #eef2ff; color: #4338ca; }
        .recon-bank { display: inline-flex; align-items: center; gap: 6px; font-size: .78rem; font-weight: 600; padding: .2rem .6rem; border-radius: 8px; background: #f1f5f9; color: #334155; }
        .recon-bank i { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
        .recon-pill { display: inline-flex; align-items: center; gap: 6px; font-size: .76rem; font-weight: 600; padding: .25rem .65rem; border-radius: 999px; }
        .recon-pill i { width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block; }
        .pill-unmatched { background: #fff4e0; color: #b45309; }
        .pill-matched   { background: #e6f6ee; color: #15803d; }
        .recon-suggest { display: inline-flex; align-items: center; gap: 5px; margin-top: 4px; font-size: .74rem; font-weight: 600; color: #15803d; background: #e6f6ee; padding: .1rem .5rem; border-radius: 6px; }

        .recon-empty { padding: 56px 16px; text-align: center; color: var(--r-muted); }
        .recon-empty i { font-size: 2.2rem; color: #cbd5e1; display: block; margin-bottom: 10px; }
        .recon-empty .t { font-weight: 600; color: #334155; }

        /* ---------- DataTables chrome (v1 + v2 class names) ---------- */
        .recon .recon-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-top: 1px solid var(--r-border); }
        .recon .dataTables_info, .recon .dt-info { color: var(--r-muted); font-size: .85rem; padding: 0 !important; }
        .recon .dataTables_length, .recon .dt-length { color: var(--r-muted); font-size: .85rem; }
        .recon .pagination { margin: 0; }
        .recon .page-link { font-size: .85rem; }
        .recon .dataTables_processing, .recon .dt-processing {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 5; background: #fff; border: 1px solid var(--r-border);
            border-radius: 10px; padding: .55rem 1rem; font-size: .85rem; color: var(--r-muted); box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        }
        .recon-scroll { position: relative; overflow-x: auto; }

        /* ---------- Modal ---------- */
        .recon-sum { border: 1px solid var(--r-border); border-radius: 12px; padding: 16px; background: #f8fafc; }
        .recon-sum .amt { font-size: 1.6rem; font-weight: 700; color: var(--r-ink); font-variant-numeric: tabular-nums; }
        .recon-sum dl { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; margin: 12px 0 0; font-size: .85rem; }
        .recon-sum dt { color: var(--r-muted); font-weight: 500; }
        .recon-sum dd { margin: 0; text-align: right; color: var(--r-ink); }
        .recon-callout { display: flex; gap: 10px; align-items: flex-start; font-size: .84rem; background: #e6f6ee; color: #14532d; border-radius: 10px; padding: .6rem .8rem; margin-top: 14px; }
        .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #dee2e6; border-radius: .375rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
    </style>

    <div class="recon container-fluid py-4" id="reconApp" data-config='@json($config)'>

        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Bank Reconciliation</h4>
                <p class="text-muted mb-0">Payments received from the bank that couldn't be assigned to a student automatically appear here for review.</p>
            </div>
            <button type="button" id="btnRefresh" class="btn btn-sm btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>

        {{-- Overview: match rate + clickable stats (filter the list) --}}
        <div class="card recon-overview mb-4">
            <div class="recon-overview-top">
                <div>
                    <div class="fw-semibold">Reconciliation overview</div>
                    <div class="text-muted small" id="rateSub">Select a figure below to filter the list</div>
                </div>
                <div class="text-end">
                    <div class="text-muted small mb-1">Match rate</div>
                    <div class="recon-rate" id="rateValue"><span class="recon-skel" style="width:70px"></span></div>
                </div>
            </div>

            <div class="recon-bar"><span id="rateBar"></span></div>

            <div class="recon-stats">
                @foreach ([
                    'unmatched' => ['Needs matching', '#f59e0b'],
                    'matched'   => ['Matched',        '#16a34a'],
                    ''          => ['All received',   '#3b82f6'],
                ] as $key => [$label, $color])
                    <button type="button" class="recon-stat" data-status="{{ $key }}" style="--accent: {{ $color }}">
                        <span class="recon-stat-label"><i></i>{{ $label }}</span>
                        <span class="recon-stat-value" id="kpi-{{ $key ?: 'all' }}-count"><span class="recon-skel"></span></span>
                        <span class="recon-stat-sub" id="kpi-{{ $key ?: 'all' }}-amount"><span class="recon-skel wide"></span></span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Load error --}}
        <div id="reconAlert" class="alert alert-danger d-none d-flex justify-content-between align-items-center" role="alert">
            <div><strong>Couldn't load transactions.</strong> <span id="reconAlertMsg"></span></div>
            <button type="button" class="btn btn-sm btn-outline-danger" id="reconRetry">Retry</button>
        </div>

        {{-- Table --}}
        <div class="card">
            <input type="hidden" id="fStatus" value="unmatched">

            <div class="recon-head">
                <div>
                    <div class="fw-semibold fs-6" id="tableTitle">Needs matching</div>
                    <div class="text-muted small" id="tableSub">Payments waiting to be assigned to a student</div>
                </div>

                <div class="recon-toolbar">
                    <div class="recon-search">
                        <i class="bi bi-search"></i>
                        <input type="search" id="fSearch" class="form-control form-control-sm" placeholder="Search reference, payer, phone, account…">
                    </div>

                    <select id="fBank" class="form-select form-select-sm w-auto">
                        <option value="">All banks</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank }}">{{ strtoupper($bank) }}</option>
                        @endforeach
                    </select>

                    <div class="recon-range">
                        <input type="date" id="fFrom" class="form-control form-control-sm" aria-label="From date">
                        <span>–</span>
                        <input type="date" id="fTo" class="form-control form-control-sm" aria-label="To date">
                    </div>

                    <button type="button" id="resetFilters" class="btn btn-sm btn-light border" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>

            <div>
                <table id="reconTable" class="table mb-0">
                    <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>Date</th>
                        <th>Bank</th>
                        <th>Reference</th>
                        <th>Payer</th>
                        <th>Account Ref</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th class="text-end"></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Match modal --}}
    <div class="modal fade" id="matchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Match to a student</h5>
                        <div class="text-muted small">A receipt is created on the student's account.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="recon-sum" id="matchSummary"></div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold" for="matchStudent">Student</label>
                        <select id="matchStudent" class="form-select"></select>
                    </div>

                    <div class="recon-callout d-none" id="matchSuggest">
                        <i class="bi bi-lightbulb"></i>
                        <div>Pre-selected from the account reference <strong id="matchSuggestRef"></strong>. Confirm it's the right student.</div>
                    </div>

                    <div id="matchError" class="text-danger small mt-3 d-none"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="matchSubmit" class="btn btn-sm btn-primary">
                        <i class="bi bi-link-45deg me-1"></i> Match &amp; create receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2100;" id="reconToasts"></div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            const cfg  = JSON.parse($('#reconApp').attr('data-config'));
            const csrf = $('meta[name="csrf-token"]').attr('content');

            const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[c]));

            const money    = (n) => Number(n || 0).toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const initials = (name) => (String(name || '?').trim().split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('') || '?').toUpperCase();

            const bankColor = { equity: '#a11d33', kcb: '#15803d', coop: '#0e7490' };
            const titles = {
                unmatched: ['Needs matching',       'Payments waiting to be assigned to a student'],
                matched:   ['Matched transactions', 'Payments already linked to a student receipt'],
                '':        ['All transactions',     'Everything received from the bank'],
            };

            const post = (url, data) => $.ajax({ url, type: 'POST', data, headers: { 'X-CSRF-TOKEN': csrf } });

            const errorText = (xhr) => {
                const j = xhr.responseJSON || {};
                const first = j.errors ? Object.values(j.errors)[0][0] : null;
                return first || j.message || `Something went wrong (HTTP ${xhr.status}). Please try again.`;
            };

            const toast = (message, type = 'success') => {
                const el = $(`
            <div class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${esc(message)}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>`);
                $('#reconToasts').append(el);
                el.on('hidden.bs.toast', () => el.remove());
                new bootstrap.Toast(el[0], { delay: 4500 }).show();
            };

            // Show load failures on the page instead of failing silently.
            $.fn.dataTable.ext.errMode = 'none';

            // ---------------------------------------------------------------- table
            const table = $('#reconTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                dom: '<"recon-scroll"rt><"recon-foot"<"recon-info"i><"recon-len"l><"recon-pager"p>>',
                ajax: {
                    url: cfg.dataUrl,
                    data: function (d) {
                        d.status    = $('#fStatus').val();
                        d.bank      = $('#fBank').val();
                        d.date_from = $('#fFrom').val();
                        d.date_to   = $('#fTo').val();
                    },
                    error: function (xhr) {
                        const j = xhr.responseJSON || {};
                        $('#reconAlertMsg').text(j.message || `Server responded with HTTP ${xhr.status}.`);
                        $('#reconAlert').removeClass('d-none');
                    }
                },
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'desc']],
                columns: [
                    {   // 0 Row number — continues across pages (like $loop->iteration)
                        data: null, orderable: false, searchable: false, className: 'text-muted',
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {   // 1 Date
                        data: 'date',
                        render: (date, type, row) => `
                    <div class="fw-semibold">${esc(date)}</div>
                    ${row.time ? `<div class="small text-muted">${esc(row.time)}</div>` : ''}`
                    },
                    {   // 2 Bank
                        data: 'bank',
                        render: (bank) => {
                            const color = bankColor[String(bank).toLowerCase()] || '#64748b';
                            return `<span class="recon-bank"><i style="background:${color}"></i>${esc(bank)}</span>`;
                        }
                    },
                    {   // 3 Reference
                        data: 'reference',
                        render: (ref) => `<span class="recon-ref">${esc(ref) || '—'}</span>`
                    },
                    {   // 4 Payer
                        data: 'payer',
                        render: (payer, type, row) => `
                    <div class="d-flex align-items-center gap-2">
                        <div class="recon-avatar">${esc(initials(payer))}</div>
                        <div>
                            <div class="fw-semibold">${esc(payer) || '—'}</div>
                            ${row.payer_phone ? `<div class="small text-muted">${esc(row.payer_phone)}</div>` : ''}
                        </div>
                    </div>`
                    },
                    {   // 5 Account ref (+ suggestion)
                        data: 'account_reference',
                        render: (ref, type, row) => {
                            let html = `<span class="recon-ref">${esc(ref) || '—'}</span>`;

                            if (row.status === 'unmatched' && row.suggestion) {
                                html += `<div><span class="recon-suggest"><i class="bi bi-lightbulb"></i>${esc(row.suggestion.text)}</span></div>`;
                            }

                            return html;
                        }
                    },
                    {   // 6 Amount
                        data: 'amount', className: 'text-end',
                        render: (amount) => `<span class="recon-amount"><span class="text-muted fw-normal me-1">KES</span>${money(amount)}</span>`
                    },
                    {   // 7 Status
                        data: 'status',
                        render: (status, type, row) => {
                            if (status === 'matched') {
                                const who  = [row.matched_to, row.matched_via].filter(Boolean).join(' · ');
                                const when = row.matched_on ? ` · ${row.matched_on}` : '';
                                return `
                            <span class="recon-pill pill-matched"><i></i>Matched</span>
                            ${who || when ? `<div class="small text-muted mt-1">${esc(who + when)}</div>` : ''}`;
                            }

                            return `<span class="recon-pill pill-unmatched"><i></i>Unmatched</span>`;
                        }
                    },
                    {   // 8 Actions
                        data: null, orderable: false, searchable: false, className: 'text-end text-nowrap',
                        render: function (row) {
                            const payload = esc(JSON.stringify({
                                reference: row.reference, amount: row.amount, payer: row.payer, payer_phone: row.payer_phone,
                                account_reference: row.account_reference, suggestion: row.suggestion, match_url: row.match_url,
                            }));

                            if (row.status === 'unmatched') {
                                if (cfg.canManage) {
                                    return `<button type="button" class="btn btn-sm btn-primary btn-match" data-config="${payload}">
                                        <i class="bi bi-link-45deg me-1"></i>Match
                                    </button>`;
                                }
                                return '';
                            }

                            return '';   // matched: read-only here (reverse it from the finance module)
                        }
                    }
                ],
                language: {
                    processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading…',
                    lengthMenu: 'Rows per page _MENU_',
                    info: 'Showing _START_–_END_ of _TOTAL_',
                    infoEmpty: 'No transactions',
                    infoFiltered: '',
                    paginate: { previous: '‹', next: '›' },
                    emptyTable: '<div class="recon-empty"><i class="bi bi-inbox"></i><div class="t">Nothing here</div><div>No transactions match the current view.</div></div>',
                    zeroRecords: '<div class="recon-empty"><i class="bi bi-search"></i><div class="t">No matching transactions</div><div>Try a different search or clear the filters.</div></div>',
                },
                initComplete: function () {
                    $('.recon .dataTables_length select, .recon .dt-length select').addClass('form-select form-select-sm d-inline-block w-auto ms-1');
                }
            });

            // ------------------------------------------------- KPI cards
            table.on('xhr.dt', function (e, settings, json) {
                if (!json || !json.summary) return;

                $('#reconAlert').addClass('d-none');

                let allCount = 0, allAmount = 0;
                const matchedCount = (json.summary.matched || { count: 0 }).count;

                ['unmatched', 'matched'].forEach((k) => {
                    const s = json.summary[k] || { count: 0, amount: 0 };
                    allCount  += s.count;
                    allAmount += s.amount;
                    $(`#kpi-${k}-count`).text(s.count.toLocaleString());
                    $(`#kpi-${k}-amount`).text('KES ' + money(s.amount));
                });

                $('#kpi-all-count').text(allCount.toLocaleString());
                $('#kpi-all-amount').text('KES ' + money(allAmount));

                const pct = allCount ? Math.round((matchedCount / allCount) * 100) : 0;
                $('#rateValue').text(pct + '%');
                $('#rateBar').css('width', pct + '%');
                $('#rateSub').text(`${matchedCount.toLocaleString()} of ${allCount.toLocaleString()} transactions matched`);
            });

            const syncStatus = () => {
                const current = $('#fStatus').val();
                $('.recon-stat').each(function () {
                    $(this).toggleClass('is-active', String($(this).data('status')) === String(current));
                });
                const [title, sub] = titles[current] || titles[''];
                $('#tableTitle').text(title);
                $('#tableSub').text(sub);
            };
            syncStatus();

            $('.recon-stat').on('click', function () {
                $('#fStatus').val(String($(this).data('status')));
                syncStatus();
                table.ajax.reload();
            });

            // ---------------------------------------------------- filters
            let searchTimeout;
            $('#fSearch').on('input', function () {
                clearTimeout(searchTimeout);
                const value = this.value;
                searchTimeout = setTimeout(() => table.search(value).draw(), 300);
            });

            let filterTimeout;
            $('#fBank, #fFrom, #fTo').on('change', function () {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => table.ajax.reload(), 150);
            });

            $('#resetFilters').on('click', function () {
                $('#fStatus').val('unmatched');
                $('#fBank, #fFrom, #fTo, #fSearch').val('');
                table.search('');
                syncStatus();
                table.ajax.reload();
            });

            $('#btnRefresh, #reconRetry').on('click', () => table.ajax.reload(null, false));

            // ---------------------------------------------------- match
            let current = null;

            $('#matchStudent').select2({
                dropdownParent: $('#matchModal'),
                width: '100%',
                placeholder: 'Search by name, admission no. or email',
                minimumInputLength: 2,
                ajax: {
                    url: cfg.studentsUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({ q: params.term }),
                }
            });

            const summaryHtml = (p) => `
        <div class="text-muted small">Amount received</div>
        <div class="amt">KES ${money(p.amount)}</div>
        <dl>
            <dt>Payer</dt><dd>${esc(p.payer) || '—'}${p.payer_phone ? ` · ${esc(p.payer_phone)}` : ''}</dd>
            <dt>Reference</dt><dd class="recon-ref">${esc(p.reference) || '—'}</dd>
            <dt>Account ref</dt><dd class="recon-ref">${esc(p.account_reference) || '—'}</dd>
        </dl>`;

            $(document).on('click', '.btn-match', function () {
                current = JSON.parse($(this).attr('data-config'));

                $('#matchError').addClass('d-none').text('');
                $('#matchSummary').html(summaryHtml(current));

                $('#matchStudent').empty();
                if (current.suggestion) {
                    $('#matchStudent').append(new Option(current.suggestion.text, current.suggestion.id, true, true));
                    $('#matchSuggestRef').text(current.account_reference);
                    $('#matchSuggest').removeClass('d-none');
                } else {
                    $('#matchSuggest').addClass('d-none');
                }
                $('#matchStudent').trigger('change');

                bootstrap.Modal.getOrCreateInstance(document.getElementById('matchModal')).show();
            });

            $('#matchSubmit').on('click', function () {
                const userId = $('#matchStudent').val();
                if (!userId) return $('#matchError').text('Select a student first.').removeClass('d-none');

                $('#matchError').addClass('d-none');
                const $btn = $(this).prop('disabled', true);

                post(current.match_url, { user_id: userId })
                    .done(() => {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('matchModal')).hide();
                        toast('Transaction matched and receipt created.');
                        table.ajax.reload(null, false);
                    })
                    .fail((xhr) => $('#matchError').text(errorText(xhr)).removeClass('d-none'))
                    .always(() => $btn.prop('disabled', false));
            });
        });
    </script>
@endpush

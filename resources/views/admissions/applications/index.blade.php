@extends('layouts.app')

@section('content')
@php
    $tabs = [
        'submitted'           => 'Needs review',
        'changes_requested'   => 'Changes requested',
        'approved'            => 'Approved',
        'interview_scheduled' => 'Interview scheduled',
        'interview_passed'    => 'Interview passed',
        'admitted'            => 'Admitted',
        'rejected'            => 'Rejected',
        'draft'               => 'In progress',
        ''                    => 'All',
    ];
@endphp

<style>
    .adm { --a-border: #e8ecf3; --a-muted: #64748b; }
    .adm .card { border: 1px solid var(--a-border); border-radius: 14px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .adm-tabs { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .25rem; }
    .adm-tab { appearance: none; border: 1px solid var(--a-border); background: #fff; border-radius: 999px; padding: .4rem .9rem; font-size: .85rem; font-weight: 600; color: #334155; white-space: nowrap; }
    .adm-tab .n { display: inline-block; min-width: 1.4rem; margin-left: .35rem; padding: 0 .35rem; border-radius: 999px; background: #eef1f6; color: var(--a-muted); font-size: .75rem; text-align: center; }
    .adm-tab:hover { background: #f8fafc; }
    .adm-tab.is-active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    .adm-tab.is-active .n { background: rgba(255, 255, 255, .25); color: #fff; }
    #admTable thead th { background: #f8fafc; color: var(--a-muted); font-size: .7rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; padding: .8rem 1rem; border-bottom: 1px solid var(--a-border); white-space: nowrap; }
    #admTable tbody td { padding: .85rem 1rem; vertical-align: middle; border-bottom: 1px solid #f0f3f8; }
    #admTable tbody tr:hover td { background: #fafbff; }
    .adm-ref { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .82rem; }
    .adm .adm-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-top: 1px solid var(--a-border); color: var(--a-muted); font-size: .85rem; }
    .adm .pagination { margin: 0; }
    .adm .page-link { font-size: .85rem; }
    .adm .dataTables_processing { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border: 1px solid var(--a-border); border-radius: 10px; padding: .5rem 1rem; z-index: 5; }
    .adm-scroll { position: relative; overflow-x: auto; }
    #admTable thead th.sorting, #admTable thead th.sorting_asc, #admTable thead th.sorting_desc { cursor: pointer; position: relative; padding-right: 1.7rem; }
    #admTable thead th.sorting::after, #admTable thead th.sorting_asc::after, #admTable thead th.sorting_desc::after { position: absolute; right: .6rem; font-size: .75rem; }
    #admTable thead th.sorting::after { content: '\2195'; opacity: .35; }
    #admTable thead th.sorting_asc::after { content: '\2191'; color: var(--bs-primary); }
    #admTable thead th.sorting_desc::after { content: '\2193'; color: var(--bs-primary); }
</style>

<div class="adm container-fluid py-4" id="admApp" data-config='@json($config)'>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Admissions</h4>
            <p class="text-muted mb-0">Review applications, book interviews and admit successful applicants.</p>
        </div>
        <div class="d-flex gap-2">
            @can('admissions.manage')
                <a href="{{ route('admissions.requirements.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders me-1"></i>Requirements &amp; settings</a>
            @endcan
            <a href="{{ route('apply.landing') }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-up-right me-1"></i>Public application page</a>
        </div>
    </div>

    <div class="adm-tabs mb-3">
        @foreach ($tabs as $status => $label)
            <button type="button" class="adm-tab {{ $status === 'submitted' ? 'is-active' : '' }}" data-status="{{ $status }}">
                {{ $label }}<span class="n">{{ $status === '' ? $counts->sum() : ($counts[$status] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    <div class="card">
        <div class="p-3 d-flex flex-wrap align-items-center gap-2 border-bottom" style="border-color: var(--a-border) !important;">
            <div class="position-relative">
                <i class="bi bi-search position-absolute text-muted" style="left:11px;top:50%;transform:translateY(-50%);"></i>
                <input type="search" id="fSearch" class="form-control form-control-sm" style="padding-left:34px;min-width:260px;" placeholder="Search reference, name, phone, email…">
            </div>
            <select id="fLevel" class="form-select form-select-sm w-auto">
                <option value="">All levels</option>
                @foreach ($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>
            <select id="fYear" class="form-select form-select-sm w-auto">
                <option value="">All years</option>
                @foreach ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>

        <table id="admTable" class="table mb-0 w-100">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Year</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    const cfg = JSON.parse($('#admApp').attr('data-config'));

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

    let status = 'submitted';   // default tab: what needs attention

    $.fn.dataTable.ext.errMode = 'none';

    const table = $('#admTable').DataTable({
        processing: true,
        serverSide: true,
        dom: '<"adm-scroll"rt><"adm-foot"<"i"i><"l"l><"p"p>>',
        ajax: {
            url: cfg.dataUrl,
            data: (d) => {
                d.status = status;
                d.education_level_id = $('#fLevel').val();
                d.academic_year = $('#fYear').val();
            },
        },
        order: [[4, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        columns: [
            { data: 'reference', render: (v, t, row) => `<a class="adm-ref fw-semibold text-decoration-none" href="${esc(row.url)}">${esc(v)}</a>` },
            {
                data: 'student',
                render: (v, t, row) => `
                    <div class="fw-semibold">${esc(v) || '<span class="text-muted fw-normal">Not filled in yet</span>'}</div>
                    <div class="small text-muted">${esc(row.contact)}${row.phone ? ' · ' + esc(row.phone) : ''}</div>`
            },
            { data: 'grade', orderable: false, render: (v) => esc(v) || '—' },
            { data: 'year', render: (v) => esc(v) || '—' },
            { data: 'submitted', render: (v) => esc(v) || '<span class="text-muted">—</span>' },
            {
                data: 'status',
                render: (v, t, row) => `<span class="badge rounded-pill text-bg-${esc(row.status_color)}">${esc(row.status_label)}</span>`
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-end',
                render: (row) => `<a class="btn btn-sm btn-outline-primary" href="${esc(row.url)}">Open</a>`
            },
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading…',
            lengthMenu: 'Rows per page _MENU_',
            info: 'Showing _START_–_END_ of _TOTAL_',
            infoEmpty: 'No applications',
            infoFiltered: '',
            paginate: { previous: '‹', next: '›' },
            emptyTable: '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-2"></i>No applications here yet.</div>',
            zeroRecords: '<div class="text-center text-muted py-5"><i class="bi bi-search fs-1 d-block mb-2"></i>No matching applications.</div>',
        },
        initComplete: () => $('.adm .dataTables_length select').addClass('form-select form-select-sm d-inline-block w-auto ms-1'),
    });

    $('.adm-tab').on('click', function () {
        status = String($(this).data('status'));
        $('.adm-tab').removeClass('is-active');
        $(this).addClass('is-active');
        table.ajax.reload();
    });

    let t;
    $('#fSearch').on('input', function () {
        clearTimeout(t);
        const v = this.value;
        t = setTimeout(() => table.search(v).draw(), 300);
    });

    $('#fLevel, #fYear').on('change', () => table.ajax.reload());
});
</script>
@endpush

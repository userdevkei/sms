$(function () {
    const table = $('#assessmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: assessmentsDataUrl,
            data: function (d) {
                d.filter_term = $('#filterTerm').val();
            }
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        searching: true,
        order: [],
        columns: [
            { data: 'sn', orderable: false },
            { data: 'name' },
            { data: 'subject' },
            { data: 'class' },
            { data: 'term' },
            { data: 'type' },
            { data: 'max_score' },
            {
                data: 'status',
                render: function (status, type, row) {
                    return `<span class="badge bg-${row.status_badge}-subtle text-${row.status_badge} text-capitalize">${status}</span>`;
                }
            },
            { data: 'actions', orderable: false, className: 'text-end' }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No assessments found - or you have no subject assignments yet.',
            zeroRecords: 'No matching assessments found.',
            search: '_INPUT_',
            searchPlaceholder: 'Search by name, subject, class, or type...'
        }
    });

    let filterTimeout;
    $('#filterTerm').on('change', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => table.ajax.reload(), 150);
    });

    $('#resetFilters').on('click', function () {
        $('#filterTerm').val('');
        table.search('').ajax.reload();
    });

    $('#assessmentsTable tbody').on('submit', '.lock-assessment-form', function (e) {
        if (!confirm('Lock this assessment? No more marks can be entered after this.')) {
            e.preventDefault();
        }
    });
});

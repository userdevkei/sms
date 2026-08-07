$(function () {
    const table = $('#roundTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: roundDataUrl },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        searching: true,
        order: [],
        columns: [
            { data: 'sn', orderable: false },
            { data: 'subject' },
            { data: 'class' },
            { data: 'type' },
            { data: 'max_score' },
            {
                data: 'status',
                render: function (status, type, row) {
                    return `<span class="badge bg-${row.status_badge}-subtle text-${row.status_badge} text-capitalize">${status}</span>`;
                }
            },
            { data: 'finalized', orderable: false },
            { data: 'actions', orderable: false, className: 'text-end' }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No assessments found for this round.',
            zeroRecords: 'No matching assessments found.',
            search: '_INPUT_',
            searchPlaceholder: 'Search by subject, class, or type...'
        }
    });

    $('#roundTable tbody').on('submit', '.lock-assessment-form', function (e) {
        if (!confirm('Lock this assessment? No more marks can be entered after this.')) {
            e.preventDefault();
        }
    });
});

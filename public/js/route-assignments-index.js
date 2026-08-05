$(function () {
    $('.select2-field').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#addAssignmentModal') });

    const table = $('#assignmentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: assignmentsDataUrl,
            data: function (d) {
                d.filter_status = $('#filterStatus').val();
            }
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[5, 'desc']],
        searching: false, // no free-text search on this endpoint — filters only
        columns: [
            { data: 'sn', orderable: false },
            { data: 'route' },
            { data: 'vehicle' },
            { data: 'driver' },
            { data: 'term' },
            { data: 'start_date' },
            { data: 'end_date' },
            {
                data: 'status',
                render: function (status) {
                    const cls = status === 'active' ? 'success' : 'secondary';
                    return `<span class="badge bg-${cls}-subtle text-${cls} text-capitalize">${status}</span>`;
                }
            },
            {
                data: null, orderable: false, className: 'text-end',
                render: function (row) {
                    if (!canManageTransport) return '';

                    let html = '';
                    if (row.status === 'active') {
                        html += `
                            <form method="POST" action="${row.end_url}" class="d-inline end-assignment-form">
                                <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                                <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="End Assignment">
                                    <i class="bi bi-stop-circle"></i>
                                </button>
                            </form>`;
                    }
                    html += `
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-assignment"
                                data-url="${row.delete_url}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>`;
                    return html;
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No assignments found.',
            zeroRecords: 'No matching assignments found.'
        }
    });

    let filterTimeout;
    $('#filterStatus').on('change', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => table.ajax.reload(), 150);
    });

    $('#resetFilters').on('click', function () {
        $('#filterStatus').val('');
        table.ajax.reload();
    });

    $(document).on('submit', '.end-assignment-form', function (e) {
        if (!confirm('End this assignment? The vehicle and driver will become available for a new route.')) {
            e.preventDefault();
        }
    });

    $(document).on('click', '.btn-delete-assignment', function () {
        const url = $(this).data('url');

        if (!confirm('Delete this assignment record?')) return;

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: (res) => res.success ? table.ajax.reload(null, false) : alert(res.message),
            error: (xhr) => alert(xhr.responseJSON?.message || 'Something went wrong. Please try again.')
        });
    });
});

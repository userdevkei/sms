$(function () {
    const table = $('#routesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: routesDataUrl,
            data: function (d) {
                d.filter_status = $('#filterStatus').val();
            }
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        columns: [
            { data: 'sn', orderable: false },
            { data: 'name' },
            { data: 'code' },
            { data: 'stops_count', className: 'text-center' },
            { data: 'fare_range' },
            {
                data: 'vehicle',
                render: (val) => val === 'Unassigned'
                    ? `<span class="text-muted fst-italic">Unassigned</span>`
                    : val
            },
            {
                data: 'driver',
                render: (val) => val === 'Unassigned'
                    ? `<span class="text-muted fst-italic">Unassigned</span>`
                    : val
            },
            {
                data: 'status',
                render: function (status) {
                    const cls = status === 'active' ? 'success' : 'secondary';
                    return `<span class="badge bg-${cls}-subtle text-${cls} text-capitalize">${status}</span>`;
                }
            },
            {
                data: null, orderable: false, searchable: false, className: 'text-end',
                render: function (row) {
                    let html = `<a href="${row.show_url}" class="btn btn-sm btn-outline-secondary me-1" title="View"><i class="bi bi-eye"></i></a>`;
                    if (canManageTransport) {
                        html += `
                            <a href="${row.edit_url}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-route"
                                    data-url="${row.delete_url}" data-name="${row.name}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>`;
                    }
                    return html;
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No routes found.',
            zeroRecords: 'No matching routes found.'
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

    $(document).on('click', '.btn-delete-route', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');

        if (!confirm(`Delete the "${name}" route? This also removes its stops.`)) return;

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: (res) => res.success ? table.ajax.reload(null, false) : alert(res.message),
            error: (xhr) => alert(xhr.responseJSON?.message || 'Something went wrong. Please try again.')
        });
    });
});

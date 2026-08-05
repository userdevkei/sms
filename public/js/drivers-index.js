$(function () {
    const table = $('#driversTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: driversDataUrl,
            data: function (d) {
                d.filter_status = $('#filterStatus').val();
            }
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        columns: [
            {
                data: 'avatar', orderable: false, searchable: false,
                render: (avatar, type, row) =>
                    `<img src="${avatar}" class="rounded-circle" width="36" height="36" style="object-fit:cover;" alt="${row.name}">`
            },
            { data: 'name' },
            { data: 'license_number' },
            {
                data: 'license_expiry',
                render: (val, type, row) => row.license_soon
                    ? `${val} <span class="badge bg-warning-subtle text-warning ms-1" title="Expiring within 30 days"><i class="bi bi-exclamation-triangle"></i></span>`
                    : val
            },
            { data: 'phone' },
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
                    if (!canManageTransport) return '';
                    return `
                        <a href="${row.edit_url}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-driver"
                                data-url="${row.delete_url}" data-name="${row.name}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>`;
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No drivers found.',
            zeroRecords: 'No matching drivers found.'
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

    $(document).on('click', '.btn-delete-driver', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');

        if (!confirm(`Remove ${name} as a driver? Their user account will not be affected.`)) return;

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: (res) => res.success ? table.ajax.reload(null, false) : alert(res.message),
            error: (xhr) => alert(xhr.responseJSON?.message || 'Something went wrong. Please try again.')
        });
    });
});

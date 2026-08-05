$(function () {
    const table = $('#vehiclesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: vehiclesDataUrl,
            data: d => { d.filter_status = $('#filterStatus').val(); }
        },
        order: [[1, 'asc']],
        columns: [
            { data: 'sn', orderable: false },
            { data: 'registration_number' },
            { data: 'make_model' },
            { data: 'capacity' },
            {
                data: 'insurance_expiry',
                render: (val, type, row) => row.insurance_soon
                    ? `${val} <span class="badge bg-warning-subtle text-warning ms-1" title="Expiring within 30 days"><i class="bi bi-exclamation-triangle"></i></span>`
                    : val
            },
            {
                data: 'inspection_expiry',
                render: (val, type, row) => row.inspection_soon
                    ? `${val} <span class="badge bg-warning-subtle text-warning ms-1" title="Expiring within 30 days"><i class="bi bi-exclamation-triangle"></i></span>`
                    : val
            },
            {
                data: 'status',
                render: function (status) {
                    const map = { active: 'success', under_maintenance: 'warning', inactive: 'secondary' };
                    const label = status.replace('_', ' ');
                    return `<span class="badge bg-${map[status]}-subtle text-${map[status]} text-capitalize">${label}</span>`;
                }
            },
            {
                data: null, orderable: false, className: 'text-end',
                render: row => {
                    let html = `<a href="${row.show_url}" class="btn btn-sm btn-outline-secondary me-1" title="View"><i class="bi bi-eye"></i></a>`;
                    if (canManageTransport) {
                        html += `<a href="${row.edit_url}" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                                  <button type="button" class="btn btn-sm btn-outline-danger btn-delete-vehicle" data-url="${row.delete_url}" title="Delete"><i class="bi bi-trash"></i></button>`;
                    }
                    return html;
                }
            }
        ],
        language: { processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...' }
    });

    $('#filterStatus').on('change', () => table.ajax.reload());

    $(document).on('click', '.btn-delete-vehicle', function () {
        const url = $(this).data('url');
        if (!confirm('Delete this vehicle?')) return;
        $.ajax({
            url, type: 'DELETE', data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: res => res.success ? table.ajax.reload(null, false) : alert(res.message),
            error: xhr => alert(xhr.responseJSON?.message || 'Something went wrong.')
        });
    });
});

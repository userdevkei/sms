$(function () {
    const table = $('#usersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: usersDataUrl,
            data: function (d) {
                d.filter_status = $('#filterStatus').val();
                d.filter_gender = $('#filterGender').val();
                d.filter_role = $('#filterRole').val();
            }
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[7, 'desc']],
        columns: [
            { data: 'sn' },
            {
                data: 'avatar', orderable: false, searchable: false,
                render: (avatar, type, row) =>
                    `<img src="${avatar}" class="rounded-circle" width="36" height="36" style="object-fit:cover;" alt="${row.name}">`
            },
            { data: 'userID' },
            { data: 'name' },
            { data: 'email' },
            { data: 'phone' },
            {
                data: 'status',
                render: function (status) {
                    const map = {
                        active: 'success', pending: 'warning', inactive: 'secondary',
                        suspended: 'danger', transferred: 'info', graduated: 'primary', deceased: 'dark'
                    };
                    const cls = map[status] || 'secondary';
                    return `<span class="badge bg-${cls}-subtle text-${cls} border border-${cls}-subtle text-capitalize">${status}</span>`;
                }
            },
            { data: 'created_at' },
            {
                data: null, orderable: false, searchable: false, className: 'text-end nowrap',
                render: function (row) {
                    if (!canManageUsers) return '';
                    return`
                        <a href="${row.profile_url}" class="btn btn-sm btn-outline-secondary me-1" title="View Profile">
                            <i class="bi bi-person"></i>
                        </a>
                        <a href="${row.edit_url}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-user"
                                data-url="${row.delete_url}" data-name="${row.name}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>`;
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
            emptyTable: 'No users found.',
            zeroRecords: 'No matching users found.'
        }
    });

    // Debounced so rapid filter changes don't fire a request per click.
    let filterTimeout;
    $('#filterStatus, #filterGender, #filterRole').on('change', function () {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => table.ajax.reload(), 150);
    });

    $('#resetFilters').on('click', function () {
        $('#filterStatus, #filterGender, #filterRole').val('');
        table.ajax.reload();
    });

    $(document).on('click', '.btn-delete-user', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');

        if (!confirm(`Delete ${name}? A Super Admin can restore this later.`)) return;

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: () => table.ajax.reload(null, false),
            error: () => alert('Something went wrong. Please try again.')
        });
    });

    $('.export-link').on('click', function (e) {
        e.preventDefault();

        const format = $(this).data('format');
        const baseUrl = format === 'pdf' ? exportPdfUrl : exportExcelUrl;

        const params = new URLSearchParams({
            filter_status: $('#filterStatus').val() || '',
            filter_gender: $('#filterGender').val() || '',
            filter_role: $('#filterRole').val() || '',
            search_value: $('.dataTables_filter input').val() || '',
        });

        window.open(`${baseUrl}?${params.toString()}`, '_blank');
    });
});

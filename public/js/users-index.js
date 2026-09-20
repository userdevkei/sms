$(function () {
    // Escapes DB values before they are placed into HTML strings/attributes.
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

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
            order: [[9, 'desc']], // created_at, newest first
            columns: [
                { data: 'sn' },
                {
                    data: 'avatar', orderable: false, searchable: false,
                    render: (avatar, type, row) =>
                        `<img src="${esc(avatar)}" class="rounded-circle" width="36" height="36" style="object-fit:cover;" alt="${esc(row.name)}">`
                },
                // render.text() makes DataTables treat these as plain text, not HTML
                { data: 'userID', render: $.fn.dataTable.render.text() },
                { data: 'name',   render: $.fn.dataTable.render.text() },
                { data: 'gender', render: $.fn.dataTable.render.text() },
                { data: 'email',  render: $.fn.dataTable.render.text() },
                { data: 'phone',  render: $.fn.dataTable.render.text() },
                { data: 'roles',  render: $.fn.dataTable.render.text() },
                {
                    data: 'status',
                    render: function (status) {
                        const map = {
                            active: 'success', pending: 'warning', inactive: 'secondary',
                            suspended: 'danger', transferred: 'info', graduated: 'primary', deceased: 'dark'
                        };
                        const cls = map[status] || 'secondary';
                        return `<span class="badge bg-${cls}-subtle text-${cls} border border-${cls}-subtle text-capitalize">${esc(status)}</span>`;
                    }
                },
                { data: 'created_at' },
                {
                    data: null, orderable: false, searchable: false, className: 'text-end nowrap',
                    render: function (row) {
                        let actions = '';

                        // Route is POST-only, so this is a link the click handler below submits as a form.
                        if (canImpersonateUser && row.impersonate_url) {
                            actions += `
                            <a href="#" class="link link-warning mx-2 js-impersonate"
                               data-url="${esc(row.impersonate_url)}" title="Access Account">
                                <i class="bi bi-lock"></i>
                            </a>`;
                        }

                        if (canViewUser) {
                            actions += `
                            <a href="${esc(row.profile_url)}" class="link link-secondary mx-2" title="View Profile">
                                <i class="bi bi-person"></i>
                            </a>`;
                        }

                        if (canUpdateUser) {
                            actions += `
                            <a href="${esc(row.edit_url)}" class="link link-primary mx-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>`;
                        }

                        if (canDeleteUser) {
                            actions += `
                            <a type="button" class="link link-danger btn-delete-user"
                               data-url="${esc(row.delete_url)}" data-name="${esc(row.name)}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>`;
                        }

                        return actions;
                    }
                }
            ],
            language: {
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...',
                emptyTable: 'No users found.',
                zeroRecords: 'No matching users found.'
            }
        });

        let filterTimeout;
        $('#filterStatus, #filterGender, #filterRole').on('change', function () {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(() => table.ajax.reload(), 150);
        });

        $('#resetFilters').on('click', function () {
            $('#filterStatus, #filterGender, #filterRole').val('');
            table.ajax.reload();
        });

        $(document).on('click', '.js-impersonate', function (e) {
            e.preventDefault();

            $('<form>', { method: 'POST', action: $(this).data('url') })
                .append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: $('meta[name="csrf-token"]').attr('content'),
                }))
                .appendTo('body')
                .get(0)
                .submit();
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
                search_value: table.search() || '',
            });

            window.open(`${baseUrl}?${params.toString()}`, '_blank');
        });
    });
});

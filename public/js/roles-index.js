$(function () {
    $('#rolesTable').DataTable({
        pageLength: 25,
        lengthMenu: [10, 25, 50],
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [2, 3, 4, 5] }],
        language: {
            emptyTable: 'No roles found.',
            zeroRecords: 'No matching roles found.'
        }
    });

    $(document).on('click', '.btn-delete-role', function () {
        const url = $(this).data('url');
        const name = $(this).data('name');
        const usersCount = $(this).data('users');

        if (usersCount > 0) {
            alert(`"${name}" is assigned to ${usersCount} user(s). Reassign them to another role before deleting.`);
            return;
        }

        if (!confirm(`Delete the "${name}" role? This cannot be undone.`)) return;

        $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                if (res.success) location.reload();
                else alert(res.message);
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Something went wrong. Please try again.');
            }
        });
    });
});

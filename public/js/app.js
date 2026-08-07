document.addEventListener('DOMContentLoaded', function () {
    var collapseToggle = document.getElementById('sidebarCollapseToggle');
    var body = document.body;
    var STORAGE_KEY = 'app-sidebar-collapsed';

    if (localStorage.getItem(STORAGE_KEY) === '1') {
        body.classList.add('sidebar-collapsed');
    }

    if (collapseToggle) {
        collapseToggle.addEventListener('click', function () {
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, body.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    }

    document.querySelectorAll('.alert').forEach(function (alertEl) {
        setTimeout(function () {
            bootstrap.Alert.getOrCreateInstance(alertEl).close();
        }, 5000);
    });
});

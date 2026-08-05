document.addEventListener('DOMContentLoaded', function () {
    // Live slug preview on the create form only
    const nameInput = document.getElementById('roleName');
    const slugPreview = document.getElementById('slugPreview');

    if (nameInput && slugPreview) {
        nameInput.addEventListener('input', function () {
            slugPreview.value = this.value
                .trim().toLowerCase()
                .replace(/[^a-z0-9\s_-]/g, '')
                .replace(/[\s-]+/g, '_');
        });
    }

    document.querySelectorAll('.module-select-all').forEach(function (moduleCheckbox) {
        moduleCheckbox.addEventListener('change', function () {
            const module = this.dataset.module;
            document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`).forEach(cb => {
                cb.checked = moduleCheckbox.checked;
            });
            updateModuleCount(module);
        });
    });

    document.querySelectorAll('.permission-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateModuleCount(this.dataset.module);
        });
    });

    function updateModuleCount(module) {
        const total = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`).length;
        const checked = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]:checked`).length;

        const badge = document.querySelector(`.module-selected-count[data-module="${module}"]`);
        if (badge) badge.textContent = `${checked}/${total}`;

        const moduleAll = document.querySelector(`.module-select-all[data-module="${module}"]`);
        if (moduleAll) moduleAll.checked = checked === total && total > 0;
    }

    const selectAll = document.getElementById('selectAllPermissions');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = selectAll.checked);
            document.querySelectorAll('.module-select-all').forEach(cb => cb.checked = selectAll.checked);
            document.querySelectorAll('.module-selected-count').forEach(badge => {
                const module = badge.dataset.module;
                const total = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`).length;
                badge.textContent = selectAll.checked ? `${total}/${total}` : `0/${total}`;
            });
        });
    }

    // Initialize counts on load (edit page has pre-checked boxes)
    document.querySelectorAll('.module-selected-count').forEach(badge => updateModuleCount(badge.dataset.module));
});

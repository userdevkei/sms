document.addEventListener('DOMContentLoaded', function () {
    const gradeLevelSelect = document.getElementById('gradeLevelSelect');
    const pathwayField = document.getElementById('pathwayField');

    function togglePathwayField() {
        const selected = gradeLevelSelect.options[gradeLevelSelect.selectedIndex];
        const isSenior = selected && selected.dataset.senior === '1';
        pathwayField.style.display = isSenior ? '' : 'none';
        if (!isSenior) {
            pathwayField.querySelector('select').value = '';
        }
    }

    if (gradeLevelSelect) {
        gradeLevelSelect.addEventListener('change', togglePathwayField);
        // Re-trigger Select2's change event too, since native 'change' doesn't
        // always fire correctly once Select2 has taken over the element.
        $(gradeLevelSelect).on('change', togglePathwayField);
        togglePathwayField(); // run once on load, for the edit page
    }
});

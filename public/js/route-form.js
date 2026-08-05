document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('stopsContainer');
    const template = document.getElementById('stopRowTemplate');
    const addBtn = document.getElementById('addStopBtn');

    if (!container || !template) return;

    let index = container.querySelectorAll('.stop-row').length;

    function renumber() {
        const rows = container.querySelectorAll('.stop-row');
        rows.forEach((row, i) => {
            const numberEl = row.querySelector('.stop-number');
            if (numberEl) numberEl.textContent = i + 1;
            else row.querySelector('.stop-row-number').textContent = i + 1;
        });

        const emptyMsg = document.getElementById('noStopsMessage');
        if (emptyMsg) emptyMsg.style.display = rows.length ? 'none' : '';
    }

    addBtn.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', index);
        container.insertAdjacentHTML('beforeend', html);
        index++;
        renumber();
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-stop')) {
            const rows = container.querySelectorAll('.stop-row');
            if (rows.length <= 1) {
                alert('A route needs at least one stop.');
                return;
            }
            e.target.closest('.stop-row').remove();
            renumber();
        }
    });
});

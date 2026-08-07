document.addEventListener('DOMContentLoaded', function () {
    const $county = $('#county');
    const $subCounty = $('#subCounty');
    const $ward = $('#ward');

    if ($county.length === 0) return;

    const subcountiesUrl = $county.data('subcounties-url');
    const wardsUrl = $county.data('wards-url');
    const presetSubCounty = $('#currentSubCounty').val() || '';
    const presetWard = $('#currentWard').val() || '';

    const select2Options = {
        theme: 'bootstrap-5',
        width: '100%',
    };

    $county.select2(select2Options);
    $subCounty.select2(select2Options);
    $ward.select2(select2Options);
    $('#ethnicity').select2({ theme: 'bootstrap-5', width: '100%' });


    function resetSelect($select, placeholder, disable = true) {
        $select.empty().append(`<option value="">${placeholder}</option>`);
        $select.prop('disabled', disable).trigger('change.select2');
    }

    function populateSelect($select, items, preselect) {
        items.forEach(item => {
            const option = new Option(item, item, item === preselect, item === preselect);
            $select.append(option);
        });
        $select.prop('disabled', items.length === 0).trigger('change.select2');
    }

    function loadSubCounties(county, preselectSub) {
        resetSelect($subCounty, 'Loading...');
        resetSelect($ward, 'Select Sub-County First');

        if (!county) {
            resetSelect($subCounty, 'Select County First');
            return;
        }

        fetch(`${subcountiesUrl}?county=${encodeURIComponent(county)}`)
            .then(res => res.json())
            .then(subcounties => {
                $subCounty.empty().append('<option value="">Select Sub-County</option>');
                populateSelect($subCounty, subcounties, preselectSub);
                if (preselectSub && subcounties.includes(preselectSub)) {
                    loadWards(county, preselectSub, presetWard);
                }
            })
            .catch(() => resetSelect($subCounty, 'Failed to load — try again'));
    }

    function loadWards(county, subcounty, preselectWard) {
        resetSelect($ward, 'Loading...');

        if (!subcounty) {
            resetSelect($ward, 'Select Sub-County First');
            return;
        }

        fetch(`${wardsUrl}?county=${encodeURIComponent(county)}&subcounty=${encodeURIComponent(subcounty)}`)
            .then(res => res.json())
            .then(wards => {
                $ward.empty().append('<option value="">Select Ward</option>');
                populateSelect($ward, wards, preselectWard);
            })
            .catch(() => resetSelect($ward, 'Failed to load — try again'));
    }

    $county.on('change', function () {
        loadSubCounties(this.value, '');
    });

    $subCounty.on('change', function () {
        loadWards($county.val(), this.value, '');
    });

    // Edit page: cascade automatically if a county is already saved.
    if ($county.val()) {
        loadSubCounties($county.val(), presetSubCounty);
    }
});


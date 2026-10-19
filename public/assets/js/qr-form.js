/*
 * Form buat QR: pilihan departemen hanya menampilkan departemen di lokasi terpilih (QR-2).
 * Server tetap menolak departemen dari lokasi lain.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-qr-form]');

    if (!form) {
        return;
    }

    var locationSelect = form.querySelector('[data-qr-location]');
    var departmentSelect = form.querySelector('[data-qr-department]');
    var emptyNote = form.querySelector('[data-qr-department-empty]');
    var options = Array.prototype.slice.call(departmentSelect.querySelectorAll('option[data-location]'));

    function filterDepartments() {
        var locationId = locationSelect.value;
        var visible = 0;

        options.forEach(function (option) {
            var matches = option.dataset.location === locationId;

            option.hidden = !matches;
            option.disabled = !matches;
            visible += matches ? 1 : 0;

            if (!matches && option.selected) {
                departmentSelect.value = '';
            }
        });

        departmentSelect.disabled = locationId === '' || visible === 0;
        emptyNote.hidden = locationId === '' || visible > 0;
    }

    locationSelect.addEventListener('change', filterDepartments);
    filterDepartments();
});

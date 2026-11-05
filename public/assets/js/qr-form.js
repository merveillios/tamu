/*
 * Form buat QR: peringatan bila lokasi terpilih belum punya departemen atau kategori
 * keperluan aktif, karena QR-nya belum dapat dipakai tamu (TAM-6). Hanya peringatan;
 * QR tetap boleh dibuat.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-qr-form]');

    if (!form) {
        return;
    }

    var locationSelect = form.querySelector('[data-qr-location]');
    var warning = form.querySelector('[data-qr-location-warning]');
    var noDepartment = warning.querySelector('[data-warning="no-department"]');
    var noCategory = warning.querySelector('[data-warning="no-category"]');

    function updateWarning() {
        var option = locationSelect.options[locationSelect.selectedIndex];
        var chosen = option && option.value !== '';
        var departments = chosen ? Number(option.dataset.departments) : 0;
        var categories = chosen ? Number(option.dataset.categories) : 0;

        noDepartment.hidden = !chosen || departments > 0;
        noCategory.hidden = !chosen || departments === 0 || categories > 0;
        warning.hidden = noDepartment.hidden && noCategory.hidden;
    }

    locationSelect.addEventListener('change', updateWarning);
    updateWarning();
});

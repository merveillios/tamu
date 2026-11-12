/*
 * Form buat QR: peringatan bila lokasi terpilih belum punya Bidang/UPT aktif, karena
 * QR-nya belum dapat dipakai tamu (TAM-6). Hanya peringatan; QR tetap boleh dibuat.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-qr-form]');

    if (!form) {
        return;
    }

    var locationSelect = form.querySelector('[data-qr-location]');
    var warning = form.querySelector('[data-qr-location-warning]');

    function updateWarning() {
        var option = locationSelect.options[locationSelect.selectedIndex];
        var chosen = option && option.value !== '';

        warning.hidden = !chosen || Number(option.dataset.departments) > 0;
    }

    locationSelect.addEventListener('change', updateWarning);
    updateWarning();
});

/*
 * Skrip umum panel staf dan halaman tamu SiTamu.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Toast dari flash session tampil otomatis setelah halaman dimuat.
    document.querySelectorAll('.toast[data-autoshow]').forEach(function (element) {
        bootstrap.Toast.getOrCreateInstance(element).show();
    });

    // Form dengan atribut data-confirm meminta konfirmasi sebelum dikirim.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});

/*
 * Skrip umum panel staf dan halaman tamu SiTamu.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Toast dari flash session tampil otomatis setelah halaman dimuat.
    document.querySelectorAll('.toast[data-autoshow]').forEach(function (element) {
        bootstrap.Toast.getOrCreateInstance(element).show();
    });
});

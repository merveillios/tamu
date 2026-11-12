/*
 * Formulir tamu: dropdown bisa dicari dan tetap terang di tema gelap HP, lalu tombol
 * kirim dinonaktifkan setelah diklik agar tidak terkirim ganda (TAM-14).
 */
(function () {
    var form = document.querySelector('[data-guest-form]');

    if (!form) {
        return;
    }

    var submit = form.querySelector('[data-submit-once]');
    var vulnerableGroup = form.querySelector('#vulnerable_group');

    if (vulnerableGroup && window.SearchableSelect) {
        SearchableSelect.enhance(vulnerableGroup);
    }

    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = 'Mengirim...';
    });
})();

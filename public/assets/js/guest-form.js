/*
 * Formulir tamu: dropdown bisa dicari dan tetap terang di tema gelap HP, rincian keperluan
 * muncul setelah kategori dipilih (TAM-3), dan tombol kirim dinonaktifkan setelah diklik
 * agar tidak terkirim ganda (TAM-14).
 */
(function () {
    var form = document.querySelector('[data-guest-form]');

    if (!form) {
        return;
    }

    var category = form.querySelector('[data-purpose-category]');
    var detail = form.querySelector('[data-purpose-detail]');
    var submit = form.querySelector('[data-submit-once]');

    [category, form.querySelector('#vulnerable_group')].forEach(function (select) {
        if (select && window.SearchableSelect) {
            SearchableSelect.enhance(select);
        }
    });

    category.addEventListener('change', function () {
        detail.hidden = category.value === '';
    });

    form.addEventListener('submit', function () {
        submit.disabled = true;
        submit.textContent = 'Mengirim...';
    });
})();

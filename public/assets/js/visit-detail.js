/*
 * Detail kunjungan: modal Buka Kembali untuk Admin (ADM-3).
 */
(function () {
    var form = document.querySelector('[data-reopen-form]');

    if (!form) {
        return;
    }

    var errorBox = form.querySelector('[data-modal-error]');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var submit = form.querySelector('[type="submit"]');
        submit.disabled = true;

        SiTamu.request(form.dataset.url, 'POST', { note: document.getElementById('reopen_note').value })
            .then(function (result) {
                if (result.body.ok) {
                    window.location.reload();
                    return;
                }

                errorBox.textContent = result.body.error ? result.body.error.message : 'Terjadi kesalahan. Silakan coba lagi.';
                errorBox.classList.remove('d-none');
            })
            .finally(function () {
                submit.disabled = false;
            });
    });
})();

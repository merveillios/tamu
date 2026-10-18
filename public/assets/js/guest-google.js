/*
 * Langkah 1 halaman tamu: tombol "Pilih akun Google" dengan Google Identity Services (TAM-11).
 * Tanpa One Tap dan tanpa auto-select; ID token dikirim ke server untuk diverifikasi.
 */
(function () {
    var container = document.querySelector('[data-google-button]');

    if (!container) {
        return;
    }

    var errorBox = document.querySelector('[data-google-error]');
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    var nonce = container.dataset.nonce;

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    }

    function handleCredential(response) {
        fetch(container.dataset.verifyUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ credential: response.credential })
        })
            .then(function (res) {
                return res.json().catch(function () {
                    return { ok: false, error: { message: 'Terlalu banyak percobaan, coba lagi beberapa menit lagi.' } };
                });
            })
            .then(function (body) {
                if (body.ok) {
                    window.location.href = body.data.redirect;
                    return;
                }

                showError(body.error && body.error.message ? body.error.message : 'Verifikasi akun Google gagal. Silakan coba lagi.');

                // Nonce lama sudah dipakai server; inisialisasi ulang dengan nonce baru.
                if (body.data && body.data.nonce) {
                    nonce = body.data.nonce;
                    renderButton();
                }
            })
            .catch(function () {
                showError('Verifikasi akun Google gagal. Silakan coba lagi.');
            });
    }

    function renderButton() {
        google.accounts.id.initialize({
            client_id: container.dataset.clientId,
            nonce: nonce,
            callback: handleCredential,
            auto_select: false,
            cancel_on_tap_outside: true
        });

        container.innerHTML = '';
        google.accounts.id.renderButton(container, {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'signin_with',
            shape: 'pill',
            locale: 'id',
            width: Math.min(container.parentElement.clientWidth, 400)
        });
    }

    // Pustaka Google dimuat async; fungsi ini dipanggil otomatis setelah pustaka siap.
    window.onGoogleLibraryLoad = renderButton;

    if (window.google && window.google.accounts && window.google.accounts.id) {
        renderButton();
    }
})();

/*
 * Langkah 1 halaman tamu: tombol resmi "Lanjutkan dengan Google" dari Google Identity Services (TAM-11).
 * Tanpa One Tap dan tanpa auto-select: One Tap bisa ditahan Google tanpa pesan apa pun,
 * sedangkan tombol resmi selalu membuka pilihan akun saat diklik. ID token dikirim ke server untuk diverifikasi.
 */
(function () {
    var container = document.querySelector('[data-google-button]');

    if (!container) {
        return;
    }

    // Batas tunggu pustaka Google sebelum tamu diberi tahu bahwa tombol gagal dimuat.
    var LOAD_TIMEOUT_MS = 10000;
    var MAX_BUTTON_WIDTH = 400;

    var errorBox = document.querySelector('[data-google-error]');
    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
    var nonce = container.dataset.nonce;

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    }

    function googleReady() {
        return window.google && window.google.accounts && window.google.accounts.id;
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

                // Nonce lama sudah dipakai server; tombol dirender ulang dengan nonce baru.
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
        var clientId = container.dataset.clientId;

        if (!clientId) {
            showError('Layanan Google Sign-In belum dikonfigurasi (GOOGLE_CLIENT_ID belum diatur).');
            return;
        }

        google.accounts.id.initialize({
            client_id: clientId,
            nonce: nonce,
            callback: handleCredential,
            auto_select: false
        });

        container.innerHTML = '';
        google.accounts.id.renderButton(container, {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'continue_with',
            shape: 'rectangular',
            logo_alignment: 'center',
            locale: 'id',
            width: Math.min(container.clientWidth || MAX_BUTTON_WIDTH, MAX_BUTTON_WIDTH)
        });
    }

    // Pustaka Google dimuat async; fungsi ini dipanggil otomatis setelah pustaka siap.
    window.onGoogleLibraryLoad = renderButton;

    if (googleReady()) {
        renderButton();
    }

    window.setTimeout(function () {
        if (!googleReady()) {
            showError('Tombol Google gagal dimuat. Periksa koneksi internet lalu muat ulang halaman, atau buka "Tidak bisa login Google?".');
        }
    }, LOAD_TIMEOUT_MS);
})();

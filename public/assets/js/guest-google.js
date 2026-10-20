/*
 * Langkah 1 halaman tamu: tombol "Lanjutkan dengan Google" dengan Google Identity Services (TAM-11).
 * Menggunakan tombol kustom SVG resmi dan memanggil One Tap/Prompt saat diklik.
 */
(function () {
    var container = document.querySelector('[data-google-button]');

    if (!container) {
        return;
    }

    var errorBox = document.querySelector('[data-google-error]');
    var csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';
    var nonce = container.dataset.nonce;

    function showError(message) {
        if (!errorBox) return;
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
                    initGoogle();
                }
            })
            .catch(function () {
                showError('Verifikasi akun Google gagal. Silakan coba lagi.');
            });
    }

    function initGoogle() {
        if (!window.google || !window.google.accounts || !window.google.accounts.id) {
            return;
        }

        var clientId = container.dataset.clientId;
        if (!clientId) {
            return;
        }

        google.accounts.id.initialize({
            client_id: clientId,
            nonce: nonce,
            callback: handleCredential,
            auto_select: false,
            cancel_on_tap_outside: true
        });
    }

    container.addEventListener('click', function () {
        var clientId = container.dataset.clientId;

        if (window.google && window.google.accounts && window.google.accounts.id && clientId) {
            google.accounts.id.prompt();
        } else if (!clientId) {
            showError('Layanan Google Sign-In belum dikonfigurasi (GOOGLE_CLIENT_ID belum diatur).');
        } else {
            showError('Pustaka Google Sign-In sedang dimuat. Silakan coba beberapa saat lagi.');
        }
    });

    // Pustaka Google dimuat async; fungsi ini dipanggil otomatis setelah pustaka siap.
    window.onGoogleLibraryLoad = initGoogle;

    if (window.google && window.google.accounts && window.google.accounts.id) {
        initGoogle();
    }
})();

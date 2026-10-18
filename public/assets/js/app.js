/*
 * Skrip umum panel staf dan halaman tamu SiTamu.
 */
window.SiTamu = (function () {
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    /**
     * Request AJAX JSON dengan header CSRF dan Accept sesuai konvensi Bab 10.
     * Selalu resolve dengan { status, body } agar pemanggil menangani 409/422 sendiri.
     */
    function request(url, method, payload) {
        return fetch(url, {
            method: method || 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            },
            body: payload ? JSON.stringify(payload) : undefined,
            credentials: 'same-origin'
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                window.location.reload();
            }

            return response.json()
                .catch(function () {
                    return { ok: false, error: { message: 'Terjadi kesalahan. Silakan coba lagi.' } };
                })
                .then(function (body) {
                    return { status: response.status, body: body };
                });
        });
    }

    /**
     * Menampilkan toast Bootstrap di pojok kanan atas.
     */
    function toast(message, type) {
        var container = document.querySelector('.toast-container');

        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(container);
        }

        var element = document.createElement('div');
        element.className = 'toast align-items-center text-white border-0 bg-' + (type === 'error' ? 'danger' : 'success');
        element.setAttribute('role', 'alert');

        var wrapper = document.createElement('div');
        wrapper.className = 'd-flex';
        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;
        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Tutup');

        wrapper.appendChild(body);
        wrapper.appendChild(close);
        element.appendChild(wrapper);
        container.appendChild(element);

        element.addEventListener('hidden.bs.toast', function () {
            element.remove();
        });
        bootstrap.Toast.getOrCreateInstance(element).show();
    }

    /**
     * Membuat elemen dengan teks aman (textContent, bukan innerHTML) sesuai A-7.
     */
    function el(tag, className, text) {
        var element = document.createElement(tag);

        if (className) {
            element.className = className;
        }

        if (text !== undefined && text !== null) {
            element.textContent = text;
        }

        return element;
    }

    return { request: request, toast: toast, el: el };
})();

document.addEventListener('DOMContentLoaded', function () {
    // Toast dari flash session tampil otomatis setelah halaman dimuat.
    document.querySelectorAll('.toast[data-autoshow]').forEach(function (element) {
        bootstrap.Toast.getOrCreateInstance(element).show();
    });

    // Tombol data-print membuka dialog cetak browser (handler inline dilarang CSP).
    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () {
            window.print();
        });
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

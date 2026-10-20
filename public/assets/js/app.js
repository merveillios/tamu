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

    /*
     * Modal Konfirmasi Simpan, Batal, & Nonaktifkan Global untuk Semua Form Staf
     */
    var saveModalEl = document.getElementById('confirmSaveModal');
    var cancelModalEl = document.getElementById('confirmCancelModal');
    var deactivateModalEl = document.getElementById('confirmDeactivateModal');

    var saveModal = saveModalEl ? bootstrap.Modal.getOrCreateInstance(saveModalEl) : null;
    var cancelModal = cancelModalEl ? bootstrap.Modal.getOrCreateInstance(cancelModalEl) : null;
    var deactivateModal = deactivateModalEl ? bootstrap.Modal.getOrCreateInstance(deactivateModalEl) : null;

    var pendingSaveForm = null;
    var pendingSaveButton = null;
    var pendingCancelTarget = null;
    var pendingDeactivateForm = null;
    var pendingDeactivateButton = null;

    function isSaveButton(button) {
        if (!button) return false;
        if (button.closest('.modal')) return false; // Jangan intersep tombol di dalam modal lain
        if (button.hasAttribute('data-confirm-save')) return true;
        if (button.hasAttribute('data-no-confirm')) return false;

        var text = (button.textContent || button.value || '').trim().toLowerCase();
        return text.indexOf('simpan') !== -1;
    }

    function isCancelButton(element) {
        if (!element) return false;
        if (element.closest('.modal')) return false; // Jangan intersep tombol di dalam modal lain
        if (element.closest('table') || element.closest('[data-active-visits]')) return false; // Tombol aksi di tabel data bukan tombol batal form
        if (element.hasAttribute('data-cancel-visit')) return false; // Tombol pembatalan kunjungan memiliki modal sendiri
        if (element.hasAttribute('data-bs-dismiss')) return false;
        if (element.hasAttribute('data-no-confirm')) return false;
        if (element.hasAttribute('data-confirm-cancel')) return true;

        var tag = element.tagName.toLowerCase();
        if (tag !== 'a' && tag !== 'button' && tag !== 'input') return false;

        var text = (element.textContent || element.value || '').trim().toLowerCase();
        return text === 'batal' || text === 'batalkan';
    }

    function isDeactivateButton(button, form) {
        if (!button) return false;
        if (button.closest('.modal')) return false; // Jangan intersep tombol di dalam modal lain
        if (button.hasAttribute('data-confirm-deactivate') || button.hasAttribute('data-deactivate-message')) return true;
        if (form && (form.hasAttribute('data-confirm-deactivate') || form.hasAttribute('data-deactivate-message'))) return true;
        if (button.hasAttribute('data-no-confirm')) return false;

        var text = (button.textContent || button.value || '').trim().toLowerCase();
        return text === 'nonaktifkan' || text.indexOf('nonaktifkan') !== -1;
    }

    function getDeactivateMessage(button, form) {
        var msg = button.getAttribute('data-deactivate-message') ||
                  (form ? form.getAttribute('data-deactivate-message') : null) ||
                  button.getAttribute('data-confirm') ||
                  (form ? form.getAttribute('data-confirm') : null);

        if (msg) {
            return msg;
        }

        var row = button.closest('tr');
        var name = '';
        if (row) {
            var nameEl = row.querySelector('.fw-bold') || row.querySelector('td:first-child');
            if (nameEl) {
                name = nameEl.textContent.trim();
            }
        }

        if (name) {
            return 'Nonaktifkan ' + name + '? Akun ini tidak bisa digunakan sampai diaktifkan kembali.';
        }

        return 'Nonaktifkan akun ini? Akun ini tidak bisa digunakan sampai diaktifkan kembali.';
    }

    // Intersep form submit yang dipicu oleh tombol Simpan atau Nonaktifkan
    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (form._sitamuConfirmed) {
            return;
        }

        if (form.closest('.modal')) {
            return;
        }

        var submitter = event.submitter || document.activeElement;
        if (!submitter || !form.contains(submitter) || submitter.type !== 'submit') {
            submitter = form.querySelector('button[type="submit"], input[type="submit"]');
        }

        if (isSaveButton(submitter)) {
            // Cek validitas form native HTML5 terlebih dahulu
            if (form.checkValidity && !form.checkValidity()) {
                form.reportValidity();
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            pendingSaveForm = form;
            pendingSaveButton = submitter;

            if (saveModal) {
                saveModal.show();
            }
            return;
        }

        if (isDeactivateButton(submitter, form)) {
            event.preventDefault();
            event.stopImmediatePropagation();

            pendingDeactivateForm = form;
            pendingDeactivateButton = submitter;

            var messageEl = document.getElementById('confirmDeactivateModalMessage');
            if (messageEl) {
                messageEl.textContent = getDeactivateMessage(submitter, form);
            }

            if (deactivateModal) {
                deactivateModal.show();
            }
            return;
        }
    }, true);

    // Tombol [Ya, Simpan] di modal konfirmasi simpan
    var saveConfirmBtn = document.getElementById('confirmSaveSubmitBtn');
    if (saveConfirmBtn) {
        saveConfirmBtn.addEventListener('click', function () {
            if (saveModal) {
                saveModal.hide();
            }

            if (pendingSaveForm) {
                var formToSubmit = pendingSaveForm;
                var buttonToSubmit = pendingSaveButton;
                pendingSaveForm = null;
                pendingSaveButton = null;

                formToSubmit._sitamuConfirmed = true;

                if (typeof formToSubmit.requestSubmit === 'function') {
                    formToSubmit.requestSubmit(buttonToSubmit || undefined);
                } else {
                    formToSubmit.submit();
                }

                setTimeout(function () {
                    formToSubmit._sitamuConfirmed = false;
                }, 1000);
            }
        });
    }

    if (saveModalEl) {
        saveModalEl.addEventListener('hidden.bs.modal', function () {
            pendingSaveForm = null;
            pendingSaveButton = null;
        });
    }

    // Tombol [Ya, Nonaktifkan] di modal konfirmasi penonaktifan
    var deactivateConfirmBtn = document.getElementById('confirmDeactivateSubmitBtn');
    if (deactivateConfirmBtn) {
        deactivateConfirmBtn.addEventListener('click', function () {
            if (deactivateModal) {
                deactivateModal.hide();
            }

            if (pendingDeactivateForm) {
                var formToSubmit = pendingDeactivateForm;
                var buttonToSubmit = pendingDeactivateButton;
                pendingDeactivateForm = null;
                pendingDeactivateButton = null;

                formToSubmit._sitamuConfirmed = true;

                if (typeof formToSubmit.requestSubmit === 'function') {
                    formToSubmit.requestSubmit(buttonToSubmit || undefined);
                } else {
                    formToSubmit.submit();
                }

                setTimeout(function () {
                    formToSubmit._sitamuConfirmed = false;
                }, 1000);
            }
        });
    }

    if (deactivateModalEl) {
        deactivateModalEl.addEventListener('hidden.bs.modal', function () {
            pendingDeactivateForm = null;
            pendingDeactivateButton = null;
        });
    }

    // Intersep klik pada tombol Batal
    document.addEventListener('click', function (event) {
        var target = event.target.closest('a, button, input');
        if (!target) return;

        if (isCancelButton(target)) {
            event.preventDefault();
            event.stopPropagation();

            pendingCancelTarget = target;
            if (cancelModal) {
                cancelModal.show();
            }
        }
    }, true);

    // Tombol [Ya, Batalkan] di modal konfirmasi pembatalan
    var cancelConfirmBtn = document.getElementById('confirmCancelActionBtn');
    if (cancelConfirmBtn) {
        cancelConfirmBtn.addEventListener('click', function () {
            if (cancelModal) {
                cancelModal.hide();
            }

            if (!pendingCancelTarget) return;

            var target = pendingCancelTarget;
            pendingCancelTarget = null;

            var collapseSelector = target.getAttribute('data-cancel-collapse');
            if (collapseSelector) {
                var collapseEl = document.querySelector(collapseSelector);
                if (collapseEl) {
                    bootstrap.Collapse.getOrCreateInstance(collapseEl).hide();
                    var input = collapseEl.querySelector('input[type="text"]');
                    if (input && input.defaultValue !== undefined) {
                        input.value = input.defaultValue;
                    }
                }
                return;
            }

            if (target.tagName.toLowerCase() === 'a' && target.href) {
                window.location.href = target.href;
                return;
            }

            if (window.history.length > 1) {
                window.history.back();
            }
        });
    }

    if (cancelModalEl) {
        cancelModalEl.addEventListener('hidden.bs.modal', function () {
            pendingCancelTarget = null;
        });
    }
});

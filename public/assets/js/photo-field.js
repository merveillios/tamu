/*
 * Kolom foto KTP dan selfie (TAM-18, RSP-11): foto diperkecil di perangkat (sisi terpanjang
 * 1600 px, JPEG mutu 0,8) sebelum dikirim, agar unggahan cepat dan tidak terbentur batas
 * ukuran server. Hasilnya dikirim sebagai data URL di field tersembunyi dan file asli
 * dikosongkan. Tanpa JavaScript, file asli dikirim apa adanya.
 *
 * Markup per kolom: [data-photo-field] berisi input file [data-photo-input], input tersembunyi
 * [data-photo-data], <img data-photo-preview>, dan [data-photo-status]. Form dengan
 * [data-photo-required] menolak dikirim tanpa foto. Tombol [data-photo-submit] dinonaktifkan
 * selama foto diproses dan saat formulir dikirim.
 */
(function () {
    var MAX_SIDE = 1600;
    var QUALITY = 0.8;

    function submitButtons(form) {
        return form.querySelectorAll('[data-photo-submit]');
    }

    function setBusy(form, busy) {
        var pending = Number(form.dataset.photoPending || 0) + (busy ? 1 : -1);
        form.dataset.photoPending = String(Math.max(pending, 0));
        submitButtons(form).forEach(function (button) {
            button.disabled = pending > 0;
        });
    }

    function compress(file, done, fail) {
        var reader = new FileReader();

        reader.onload = function () {
            var image = new Image();

            image.onload = function () {
                var scale = Math.min(1, MAX_SIDE / Math.max(image.naturalWidth, image.naturalHeight));
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(image.naturalWidth * scale);
                canvas.height = Math.round(image.naturalHeight * scale);

                var context = canvas.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                done(canvas.toDataURL('image/jpeg', QUALITY));
            };

            image.onerror = fail;
            image.src = reader.result;
        };

        reader.onerror = fail;
        reader.readAsDataURL(file);
    }

    document.querySelectorAll('[data-photo-field]').forEach(function (field) {
        var form = field.closest('form');
        var input = field.querySelector('[data-photo-input]');
        var data = field.querySelector('[data-photo-data]');
        var preview = field.querySelector('[data-photo-preview]');
        var status = field.querySelector('[data-photo-status]');

        input.addEventListener('change', function () {
            if (!input.files || input.files.length === 0) {
                return;
            }

            data.value = '';
            status.textContent = 'Memperkecil foto...';
            setBusy(form, true);

            compress(input.files[0], function (dataUrl) {
                data.value = dataUrl;
                input.value = '';
                preview.src = dataUrl;
                preview.hidden = false;
                status.textContent = 'Foto siap disimpan (' + Math.round(dataUrl.length * 0.75 / 1024) + ' KB).';
                setBusy(form, false);
            }, function () {
                status.textContent = 'Foto tidak dapat dibaca. Gunakan foto JPG, PNG, atau WebP.';
                setBusy(form, false);
            });
        });
    });

    document.querySelectorAll('form').forEach(function (form) {
        if (!form.querySelector('[data-photo-field]')) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.hasAttribute('data-photo-required')) {
                var field = form.querySelector('[data-photo-field]');
                var input = field.querySelector('[data-photo-input]');

                if (field.querySelector('[data-photo-data]').value === '' && (!input.files || input.files.length === 0)) {
                    event.preventDefault();
                    field.querySelector('[data-photo-status]').textContent = 'Pilih foto terlebih dahulu.';
                    return;
                }
            }

            // Dialog "Konfirmasi Simpan" di app.js menahan pengiriman pertama (preventDefault)
            // setelah handler ini berjalan. Tombol baru dinonaktifkan bila pengiriman benar-benar
            // berlanjut, agar tidak tertinggal "Menyimpan..." saat pengguna memilih Kembali.
            setTimeout(function () {
                if (event.defaultPrevented) {
                    return;
                }

                submitButtons(form).forEach(function (button) {
                    button.disabled = true;
                    button.textContent = 'Menyimpan...';
                });
            }, 0);
        });
    });
})();

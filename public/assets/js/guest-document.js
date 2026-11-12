/*
 * "Lengkapi profil" di riwayat tamu (TAM-18): foto KTP dan selfie diperkecil di HP
 * (sisi terpanjang 1600 px, JPEG mutu 0,8) sebelum dikirim, agar unggahan cepat dan
 * tidak terbentur batas ukuran server. Hasilnya dikirim sebagai data URL di field
 * tersembunyi; file asli dikosongkan. Tanpa JavaScript, file asli dikirim apa adanya.
 */
(function () {
    var MAX_SIDE = 1600;
    var QUALITY = 0.8;

    document.querySelectorAll('[data-guest-document]').forEach(function (form) {
        var input = form.querySelector('[data-document-input]');
        var data = form.querySelector('[data-document-data]');
        var preview = form.querySelector('[data-document-preview]');
        var status = form.querySelector('[data-document-status]');
        var submit = form.querySelector('[data-document-submit]');

        function setStatus(text) {
            status.textContent = text;
        }

        function compress(file) {
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

                    var dataUrl = canvas.toDataURL('image/jpeg', QUALITY);
                    data.value = dataUrl;
                    input.value = '';
                    preview.src = dataUrl;
                    preview.hidden = false;
                    submit.disabled = false;
                    setStatus('Foto siap disimpan (' + Math.round(dataUrl.length * 0.75 / 1024) + ' KB).');
                };

                image.onerror = function () {
                    data.value = '';
                    submit.disabled = false;
                    setStatus('Foto tidak dapat dibaca. Gunakan foto JPG, PNG, atau WebP.');
                };

                image.src = reader.result;
            };

            reader.readAsDataURL(file);
        }

        input.addEventListener('change', function () {
            if (!input.files || input.files.length === 0) {
                return;
            }

            data.value = '';
            submit.disabled = true;
            setStatus('Memperkecil foto...');
            compress(input.files[0]);
        });

        form.addEventListener('submit', function (event) {
            if (data.value === '' && (!input.files || input.files.length === 0)) {
                event.preventDefault();
                setStatus('Pilih foto terlebih dahulu.');
                return;
            }

            submit.disabled = true;
            submit.textContent = 'Menyimpan...';
        });
    });
})();

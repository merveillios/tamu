/*
 * Kolom foto KTP dan selfie (TAM-18, RSP-11).
 *
 * Alur: pilih sumber (tombol "Ambil foto" membuka kamera, "Pilih dari galeri" membuka
 * galeri/berkas) -> area potong muncul: kotak dapat digeser, sudutnya ditarik untuk
 * mengubah ukuran, dan foto dapat diputar 90 derajat -> "Gunakan foto" menyimpan hasil
 * potongan sebagai JPEG (sisi terpanjang 1600 px, mutu 0,85) di field tersembunyi.
 * Semua proses terjadi di perangkat; server tetap membaca ulang dan memeriksa foto.
 *
 * Tanpa JavaScript, tombol kamera tetap tersembunyi dan input galeri yang bernama
 * dikirim apa adanya.
 *
 * Markup per kolom: [data-photo-field data-photo-type="ktp|selfie"] berisi input
 * tersembunyi [data-photo-data], <img data-photo-preview>, satu atau lebih input file
 * [data-photo-input], tombol [data-photo-js] (tersembunyi sampai JS jalan), input galeri
 * biasa [data-photo-fallback] (disembunyikan bila JS jalan), dan [data-photo-status].
 * Markup sumber foto ada di partials/photo-sources.blade.php. Form dengan [data-photo-required] menolak dikirim tanpa foto;
 * tombol [data-photo-submit] dinonaktifkan selama foto diproses atau sedang dipotong.
 */
(function () {
    var OUTPUT_MAX_SIDE = 1600;
    var WORK_MAX_SIDE = 2400; // batas kanvas kerja agar foto 48 MP tidak melampaui batas memori HP
    var QUALITY = 0.85;
    var MIN_OUTPUT_SIDE = 300; // sama dengan batas server (GuestDocumentService::MIN_SIDE)
    var HANDLE = 22; // jari-jari sentuh sudut kotak potong, piksel layar
    var KTP_RATIO = 85.6 / 54; // ukuran kartu ID-1

    function el(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (text) element.textContent = text;
        return element;
    }

    function setBusy(form, busy) {
        var pending = Math.max(Number(form.dataset.photoPending || 0) + (busy ? 1 : -1), 0);
        form.dataset.photoPending = String(pending);
        form.querySelectorAll('[data-photo-submit]').forEach(function (button) {
            button.disabled = pending > 0;
        });
    }

    function loadImage(file, done, fail) {
        var reader = new FileReader();
        reader.onload = function () {
            var image = new Image();
            image.onload = function () { done(image); };
            image.onerror = fail;
            image.src = reader.result;
        };
        reader.onerror = fail;
        reader.readAsDataURL(file);
    }

    // Kanvas kerja: foto diperkecil ke WORK_MAX_SIDE lalu diputar (kelipatan 90 derajat).
    function workCanvas(image, quarterTurns) {
        var scale = Math.min(1, WORK_MAX_SIDE / Math.max(image.naturalWidth, image.naturalHeight));
        var width = Math.round(image.naturalWidth * scale);
        var height = Math.round(image.naturalHeight * scale);
        var turned = quarterTurns % 2 === 1;
        var canvas = document.createElement('canvas');
        canvas.width = turned ? height : width;
        canvas.height = turned ? width : height;

        var context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.translate(canvas.width / 2, canvas.height / 2);
        context.rotate(quarterTurns * Math.PI / 2);
        context.drawImage(image, -width / 2, -height / 2, width, height);

        return canvas;
    }

    function initialRect(width, height, type) {
        var ratio = type === 'ktp' ? KTP_RATIO : 3 / 4;
        var w = width * 0.9;
        var h = w / ratio;

        if (h > height * 0.9) {
            h = height * 0.9;
            w = h * ratio;
        }

        return { x: (width - w) / 2, y: (height - h) / 2, w: w, h: h };
    }

    function Cropper(field, onDone, onCancel) {
        var type = field.dataset.photoType || 'ktp';
        var panel = el('div', 'photo-cropper border rounded p-2 mb-2 bg-light');
        var hint = el('div', 'small text-gray-600 mb-2',
            'Geser kotak untuk memilih bagian foto, tarik sudutnya untuk mengubah ukuran.');
        var display = el('canvas', 'photo-cropper-canvas');
        var actions = el('div', 'd-flex flex-wrap gap-2 mt-2');
        var rotate = el('button', 'btn btn-sm btn-outline-gray-800', 'Putar');
        var cancel = el('button', 'btn btn-sm btn-outline-gray-800', 'Ganti foto');
        var use = el('button', 'btn btn-sm btn-primary', 'Gunakan foto');

        [rotate, cancel, use].forEach(function (button) {
            button.type = 'button';
            // Jangan dicegat dialog konfirmasi global di app.js.
            button.setAttribute('data-no-confirm', 'true');
        });

        actions.appendChild(rotate);
        actions.appendChild(cancel);
        actions.appendChild(use);
        panel.appendChild(hint);
        panel.appendChild(display);
        panel.appendChild(actions);

        var image = null;
        var turns = 0;
        var source = null;
        var scale = 1;
        var rect = null;
        var drag = null;

        function layout() {
            source = workCanvas(image, turns);
            var maxWidth = Math.max(panel.clientWidth - 16, 200);
            var maxHeight = Math.max(window.innerHeight * 0.6, 240);
            scale = Math.min(maxWidth / source.width, maxHeight / source.height, 1);
            display.width = Math.round(source.width * scale);
            display.height = Math.round(source.height * scale);
            rect = initialRect(display.width, display.height, type);
            draw();
        }

        function draw() {
            var context = display.getContext('2d');
            context.clearRect(0, 0, display.width, display.height);
            context.drawImage(source, 0, 0, display.width, display.height);

            // Gelapkan bagian di luar kotak potong.
            context.fillStyle = 'rgba(0, 0, 0, 0.5)';
            context.fillRect(0, 0, display.width, rect.y);
            context.fillRect(0, rect.y + rect.h, display.width, display.height - rect.y - rect.h);
            context.fillRect(0, rect.y, rect.x, rect.h);
            context.fillRect(rect.x + rect.w, rect.y, display.width - rect.x - rect.w, rect.h);

            context.strokeStyle = '#ffffff';
            context.lineWidth = 2;
            context.strokeRect(rect.x, rect.y, rect.w, rect.h);

            context.fillStyle = '#ffffff';
            corners().forEach(function (corner) {
                context.fillRect(corner.x - 6, corner.y - 6, 12, 12);
            });
        }

        function corners() {
            return [
                { name: 'nw', x: rect.x, y: rect.y },
                { name: 'ne', x: rect.x + rect.w, y: rect.y },
                { name: 'sw', x: rect.x, y: rect.y + rect.h },
                { name: 'se', x: rect.x + rect.w, y: rect.y + rect.h },
            ];
        }

        function point(event) {
            var bounds = display.getBoundingClientRect();
            return {
                x: (event.clientX - bounds.left) * (display.width / bounds.width),
                y: (event.clientY - bounds.top) * (display.height / bounds.height),
            };
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        display.addEventListener('pointerdown', function (event) {
            var p = point(event);
            var corner = corners().find(function (c) {
                return Math.abs(c.x - p.x) <= HANDLE && Math.abs(c.y - p.y) <= HANDLE;
            });
            var inside = p.x >= rect.x && p.x <= rect.x + rect.w && p.y >= rect.y && p.y <= rect.y + rect.h;

            if (!corner && !inside) {
                return;
            }

            drag = { corner: corner ? corner.name : null, start: p, rect: Object.assign({}, rect) };
            display.setPointerCapture(event.pointerId);
            event.preventDefault();
        });

        display.addEventListener('pointermove', function (event) {
            if (!drag) {
                return;
            }

            var p = point(event);
            var dx = p.x - drag.start.x;
            var dy = p.y - drag.start.y;
            var r = drag.rect;
            var min = 40;

            if (!drag.corner) {
                rect.x = clamp(r.x + dx, 0, display.width - r.w);
                rect.y = clamp(r.y + dy, 0, display.height - r.h);
            } else {
                var left = r.x;
                var top = r.y;
                var right = r.x + r.w;
                var bottom = r.y + r.h;

                if (drag.corner.indexOf('w') !== -1) left = clamp(r.x + dx, 0, right - min);
                if (drag.corner.indexOf('e') !== -1) right = clamp(right + dx, left + min, display.width);
                if (drag.corner.indexOf('n') !== -1) top = clamp(r.y + dy, 0, bottom - min);
                if (drag.corner.indexOf('s') !== -1) bottom = clamp(bottom + dy, top + min, display.height);

                rect = { x: left, y: top, w: right - left, h: bottom - top };
            }

            draw();
            event.preventDefault();
        });

        ['pointerup', 'pointercancel'].forEach(function (name) {
            display.addEventListener(name, function () { drag = null; });
        });

        rotate.addEventListener('click', function () {
            turns = (turns + 1) % 4;
            layout();
        });

        cancel.addEventListener('click', function () {
            close();
            onCancel();
        });

        use.addEventListener('click', function () {
            var sx = Math.round(rect.x / scale);
            var sy = Math.round(rect.y / scale);
            var sw = Math.round(rect.w / scale);
            var sh = Math.round(rect.h / scale);
            var outScale = Math.min(1, OUTPUT_MAX_SIDE / Math.max(sw, sh));
            var out = document.createElement('canvas');
            out.width = Math.round(sw * outScale);
            out.height = Math.round(sh * outScale);

            if (Math.min(out.width, out.height) < MIN_OUTPUT_SIDE) {
                hint.textContent = 'Bagian yang dipilih terlalu kecil. Perbesar kotak atau ambil foto lebih dekat.';
                hint.classList.add('text-danger');
                return;
            }

            var context = out.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, out.width, out.height);
            context.drawImage(source, sx, sy, sw, sh, 0, 0, out.width, out.height);

            close();
            onDone(out.toDataURL('image/jpeg', QUALITY));
        });

        function close() {
            panel.remove();
        }

        this.open = function (loadedImage, anchor) {
            image = loadedImage;
            turns = 0;
            anchor.parentNode.insertBefore(panel, anchor);
            layout();
        };
    }

    document.querySelectorAll('[data-photo-field]').forEach(function (field) {
        var form = field.closest('form');
        var inputs = field.querySelectorAll('[data-photo-input]');
        var data = field.querySelector('[data-photo-data]');
        var preview = field.querySelector('[data-photo-preview]');
        var status = field.querySelector('[data-photo-status]');
        var sources = field.querySelector('[data-photo-sources]') || inputs[0];
        var originalStatus = status.textContent;

        // Dengan JavaScript: tampilkan tombol "Ambil foto" dan "Pilih dari galeri", sembunyikan
        // input berkas biasa (tetap dipakai lewat label "Pilih dari galeri").
        field.querySelectorAll('[data-photo-js]').forEach(function (button) {
            button.hidden = false;
        });
        field.querySelectorAll('[data-photo-fallback]').forEach(function (input) {
            input.classList.add('visually-hidden');
        });

        var cropper = new Cropper(field, function (dataUrl) {
            data.value = dataUrl;
            preview.src = dataUrl;
            preview.hidden = false;
            status.textContent = 'Foto siap disimpan (' + Math.round(dataUrl.length * 0.75 / 1024) + ' KB).';
            setBusy(form, false);
        }, function () {
            status.textContent = originalStatus;
            setBusy(form, false);
        });

        inputs.forEach(function (input) {
            input.addEventListener('change', function () {
                if (!input.files || input.files.length === 0) {
                    return;
                }

                var file = input.files[0];
                // Berkas asli tidak ikut dikirim; yang dikirim hasil potongan.
                input.value = '';
                data.value = '';
                status.textContent = 'Membuka foto...';
                setBusy(form, true);

                loadImage(file, function (image) {
                    status.textContent = '';
                    cropper.open(image, sources);
                }, function () {
                    status.textContent = 'Foto tidak dapat dibaca. Gunakan foto JPG, PNG, atau WebP.';
                    setBusy(form, false);
                });
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
                var hasFile = Array.prototype.some.call(field.querySelectorAll('[data-photo-input]'), function (input) {
                    return input.files && input.files.length > 0;
                });

                if (field.querySelector('[data-photo-data]').value === '' && !hasFile) {
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

                form.querySelectorAll('[data-photo-submit]').forEach(function (button) {
                    button.disabled = true;
                    button.textContent = 'Menyimpan...';
                });
            }, 0);
        });
    });
})();

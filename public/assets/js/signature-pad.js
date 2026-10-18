/*
 * Kotak tanda tangan berbasis <canvas> dan Pointer Events (TAM-9).
 * Hasilnya dikirim sebagai data URL PNG di field tersembunyi saat formulir dikirim.
 */
(function () {
    var canvas = document.querySelector('[data-signature-canvas]');

    if (!canvas) {
        return;
    }

    var input = document.querySelector('[data-signature-input]');
    var clearButton = document.querySelector('[data-signature-clear]');
    var context = canvas.getContext('2d');
    var drawing = false;
    var hasInk = false;

    // Resolusi dibatasi 2x agar dimensi tetap dalam batas server (maks. 1200 x 600 px).
    function resize() {
        var ratio = Math.min(window.devicePixelRatio || 1, 2);
        var rect = canvas.getBoundingClientRect();

        canvas.width = Math.round(rect.width * ratio);
        canvas.height = Math.round(rect.height * ratio);
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.lineWidth = 2.5;
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.strokeStyle = '#111827';
        hasInk = false;
    }

    function point(event) {
        var rect = canvas.getBoundingClientRect();

        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    canvas.addEventListener('pointerdown', function (event) {
        event.preventDefault();
        canvas.setPointerCapture(event.pointerId);
        drawing = true;

        var p = point(event);
        context.beginPath();
        context.moveTo(p.x, p.y);
    });

    canvas.addEventListener('pointermove', function (event) {
        if (!drawing) {
            return;
        }

        event.preventDefault();
        var p = point(event);
        context.lineTo(p.x, p.y);
        context.stroke();
        hasInk = true;
    });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (type) {
        canvas.addEventListener(type, function () {
            drawing = false;
        });
    });

    clearButton.addEventListener('click', function () {
        context.clearRect(0, 0, canvas.width, canvas.height);
        hasInk = false;
        input.value = '';
    });

    canvas.closest('form').addEventListener('submit', function () {
        input.value = hasInk ? canvas.toDataURL('image/png') : '';
    });

    resize();
})();

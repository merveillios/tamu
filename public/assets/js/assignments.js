/*
 * Penugasan PIC: polling daftar (PIC-4), tombol Terima (PIC-2), modal Hasil (PIC-3),
 * dan daftar Selesai hari ini yang hanya bisa dilihat (PIC-7).
 * Jumlah penugasan baru tampil di judul tab dan badge menu; bunyi singkat diputar bila
 * PIC sudah mengaktifkannya lewat tombol di halaman.
 */
(function () {
    var root = document.querySelector('[data-assignments]');

    if (!root) {
        return;
    }

    var el = SiTamu.el;
    var pollSeconds = parseInt(root.dataset.poll, 10) || 30;
    var baseTitle = document.title;
    var knownIds = null;
    var timer = null;
    var currentVisit = null;
    var modalElement = document.getElementById('completeModal');
    var form = document.querySelector('[data-complete-form]');
    var resultInput = document.getElementById('complete_result');
    var soundButton = document.querySelector('[data-sound-toggle]');
    var AudioContextClass = window.AudioContext || window.webkitAudioContext;
    var audioContext = null;
    var soundEnabled = false;

    // Dua nada pendek dari Web Audio API; tidak perlu file audio sehingga tetap sesuai CSP 'self'.
    function playNotification() {
        if (!soundEnabled || !audioContext) {
            return;
        }

        var start = audioContext.currentTime;

        [0, 0.2].forEach(function (offset) {
            var oscillator = audioContext.createOscillator();
            var gain = audioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.0001, start + offset);
            gain.gain.exponentialRampToValueAtTime(0.3, start + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.15);
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            oscillator.start(start + offset);
            oscillator.stop(start + offset + 0.16);
        });
    }

    function updateSoundButton() {
        soundButton.textContent = soundEnabled ? 'Matikan bunyi notifikasi' : 'Aktifkan bunyi notifikasi';
        soundButton.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
        soundButton.classList.toggle('btn-primary', soundEnabled);
        soundButton.classList.toggle('btn-outline-gray-800', !soundEnabled);
    }

    if (soundButton && AudioContextClass) {
        soundButton.hidden = false;

        // Browser hanya mengizinkan audio setelah interaksi pengguna, jadi AudioContext dibuat saat tombol diklik.
        soundButton.addEventListener('click', function () {
            audioContext = audioContext || new AudioContextClass();
            audioContext.resume();
            soundEnabled = !soundEnabled;
            updateSoundButton();
            playNotification();
        });
    }

    function actionUrl(template, id) {
        return template.replace('__ID__', id);
    }

    function cell(label, content, className) {
        var td = el('td', className);
        td.setAttribute('data-label', label);

        if (content instanceof Node) {
            td.appendChild(content);
        } else {
            td.textContent = content === null || content === undefined || content === '' ? '–' : content;
        }

        return td;
    }

    function actions(row) {
        var wrap = el('div', 'd-flex flex-wrap gap-1 justify-content-end justify-content-md-start');

        if (row.actions.accept) {
            var accept = el('button', 'btn btn-sm btn-primary', 'Terima');
            accept.type = 'button';
            accept.addEventListener('click', function () { acceptVisit(row, accept); });
            wrap.appendChild(accept);
        }

        if (row.actions.complete) {
            var complete = el('button', 'btn btn-sm btn-success', 'Selesai');
            complete.type = 'button';
            complete.addEventListener('click', function () { openComplete(row); });
            wrap.appendChild(complete);
        }

        var detail = el('a', 'btn btn-sm btn-outline-gray-800', 'Detail');
        detail.href = row.detail_url;
        wrap.appendChild(detail);

        return wrap;
    }

    function renderRow(row) {
        var tr = el('tr');
        var purpose = el('div');
        purpose.appendChild(el('div', 'fw-bold', row.category));
        purpose.appendChild(el('div', 'small text-gray-600 text-break', row.purpose));

        if (knownIds && !knownIds.has(row.id)) {
            tr.classList.add('row-new');
        }

        if (row.waiting_long) {
            tr.classList.add('row-waiting-long');
        }

        tr.appendChild(cell('No.', 'No. ' + row.number, 'fw-bold'));
        tr.appendChild(cell('Nama', row.name, 'fw-bold'));
        tr.appendChild(cell('Instansi', row.organization));
        tr.appendChild(cell('Kelompok rentan', row.vulnerable_group));
        tr.appendChild(cell('Keperluan', purpose));
        tr.appendChild(cell('Ditemui', row.served_by));
        tr.appendChild(cell('Lokasi', row.location));
        tr.appendChild(cell('Jam datang', row.check_in));
        tr.appendChild(cell('Menunggu', row.waiting_minutes + ' mnt'));
        tr.appendChild(cell('Aksi', actions(row)));

        return tr;
    }

    function renderCompletedRow(row) {
        var tr = el('tr');
        var purpose = el('div');
        purpose.appendChild(el('div', 'fw-bold', row.category));
        purpose.appendChild(el('div', 'small text-gray-600 text-break', row.purpose));

        var detail = el('a', 'btn btn-sm btn-outline-gray-800', 'Detail');
        detail.href = row.detail_url;

        tr.appendChild(cell('No.', 'No. ' + row.number, 'fw-bold'));
        tr.appendChild(cell('Nama', row.name, 'fw-bold'));
        tr.appendChild(cell('Instansi', row.organization));
        tr.appendChild(cell('Keperluan', purpose));
        tr.appendChild(cell('Ditemui', row.served_by));
        tr.appendChild(cell('Jam selesai', row.completed_at));
        tr.appendChild(cell('Hasil', row.result_excerpt, 'small text-break'));
        tr.appendChild(cell('Aksi', detail));

        return tr;
    }

    function renderGroup(name, rows, rowRenderer, columns) {
        var tbody = root.querySelector('[data-group="' + name + '"]');
        tbody.textContent = '';

        if (rows.length === 0) {
            var empty = el('tr');
            var td = el('td', 'text-center text-gray-600 py-4', 'Tidak ada data.');
            td.colSpan = columns;
            empty.appendChild(td);
            tbody.appendChild(empty);
        }

        rows.forEach(function (row) {
            tbody.appendChild(rowRenderer(row));
        });

        root.querySelector('[data-group-count="' + name + '"]').textContent = rows.length;
    }

    function render(data) {
        // Pemuatan pertama tidak dianggap penugasan baru agar bunyi tidak berbunyi saat halaman dibuka.
        var hasFreshAssignment = knownIds !== null && data.new.some(function (row) { return !knownIds.has(row.id); });

        renderGroup('new', data.new, renderRow, 10);
        renderGroup('reviewing', data.reviewing, renderRow, 10);
        renderGroup('completed', data.completed, renderCompletedRow, 8);

        knownIds = knownIds || new Set();
        data.new.concat(data.reviewing).forEach(function (row) { knownIds.add(row.id); });

        if (hasFreshAssignment) {
            playNotification();
        }

        document.title = (data.new_count > 0 ? '(' + data.new_count + ') ' : '') + baseTitle;
        document.querySelectorAll('[data-assignment-badge]').forEach(function (badge) {
            badge.textContent = data.new_count;
            badge.hidden = data.new_count === 0;
        });
        document.querySelector('[data-server-time]').textContent = data.server_time;
    }

    function load() {
        return SiTamu.request(root.dataset.url).then(function (result) {
            if (result.body.ok) {
                render(result.body.data);
            }
        });
    }

    function startPolling() {
        stopPolling();
        timer = window.setInterval(load, pollSeconds * 1000);
    }

    function stopPolling() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    var completedToggle = root.querySelector('[data-completed-toggle]');
    var completedPanel = document.getElementById('completedToday');

    completedPanel.addEventListener('shown.bs.collapse', function () { completedToggle.textContent = 'Sembunyikan'; });
    completedPanel.addEventListener('hidden.bs.collapse', function () { completedToggle.textContent = 'Tampilkan'; });

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopPolling();
        } else {
            load();
            startPolling();
        }
    });

    function acceptVisit(row, button) {
        button.disabled = true;

        SiTamu.request(actionUrl(root.dataset.acceptUrl, row.id), 'POST').then(function (result) {
            SiTamu.toast(result.body.ok ? result.body.data.message : result.body.error.message, result.body.ok ? 'success' : 'error');
            load();
        });
    }

    function showError(message) {
        var box = form.querySelector('[data-modal-error]');
        box.textContent = message;
        box.classList.toggle('d-none', !message);
    }

    function openComplete(row) {
        currentVisit = row;
        form.reset();
        showError('');
        form.querySelector('[data-complete-guest]').textContent = 'No. ' + row.number + ' · ' + row.name + ' · ' + row.category;
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var submit = form.querySelector('[type="submit"]');
        submit.disabled = true;

        SiTamu.request(actionUrl(root.dataset.completeUrl, currentVisit.id), 'POST', { result: resultInput.value })
            .then(function (result) {
                if (result.body.ok || result.status === 409) {
                    bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    SiTamu.toast(result.body.ok ? result.body.data.message : result.body.error.message, result.body.ok ? 'success' : 'error');
                    load();
                    return;
                }

                showError(result.body.error ? result.body.error.message : 'Terjadi kesalahan. Silakan coba lagi.');
            })
            .finally(function () {
                submit.disabled = false;
            });
    });

    load();
    startPolling();
})();

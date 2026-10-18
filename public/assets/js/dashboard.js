/*
 * Dashboard: grafik kunjungan 14 hari (DSH-3) dan tombol Batal pada Tamu Aktif ringkas (DSH-2).
 */
(function () {
    var canvas = document.querySelector('[data-visits-chart]');

    if (canvas && window.Chart) {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(canvas.dataset.labels),
                datasets: [{
                    label: 'Kunjungan',
                    data: JSON.parse(canvas.dataset.values),
                    backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--sitamu-primary').trim() || '#1e40af',
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var activeCard = document.querySelector('[data-dashboard-active]');
    var cancelForm = document.querySelector('[data-cancel-form]');

    if (!activeCard || !cancelForm) {
        return;
    }

    var modalElement = document.getElementById('cancelModal');
    var reasonSelect = document.getElementById('cancel_reason');
    var otherWrap = cancelForm.querySelector('[data-cancel-other]');
    var errorBox = cancelForm.querySelector('[data-modal-error]');
    var visitId = null;

    activeCard.querySelectorAll('[data-cancel-visit]').forEach(function (button) {
        button.addEventListener('click', function () {
            visitId = button.dataset.cancelVisit;
            cancelForm.reset();
            otherWrap.hidden = true;
            errorBox.classList.add('d-none');
            cancelForm.querySelector('[data-cancel-guest]').textContent = button.dataset.guest;
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    reasonSelect.addEventListener('change', function () {
        otherWrap.hidden = reasonSelect.value !== 'other';
    });

    cancelForm.addEventListener('submit', function (event) {
        event.preventDefault();

        SiTamu.request(activeCard.dataset.cancelUrl.replace('__ID__', visitId), 'POST', {
            reason: reasonSelect.value,
            reason_text: document.getElementById('cancel_reason_text').value
        }).then(function (result) {
            if (result.body.ok || result.status === 409) {
                window.location.reload();
                return;
            }

            errorBox.textContent = result.body.error ? result.body.error.message : 'Terjadi kesalahan. Silakan coba lagi.';
            errorBox.classList.remove('d-none');
        });
    });
})();

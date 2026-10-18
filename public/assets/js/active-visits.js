/*
 * Tamu Aktif: daftar diperbarui lewat polling (RSP-7), aksi Tugaskan dan Batal tanpa
 * pindah halaman (RSP-5). Semua teks dirender dengan textContent (A-7).
 */
(function () {
    var table = document.querySelector('[data-active-visits]');

    if (!table) {
        return;
    }

    var el = SiTamu.el;
    var tbody = table.querySelector('tbody');
    var showLocation = table.dataset.showLocation === '1';
    var pollSeconds = parseInt(table.dataset.poll, 10) || 30;
    var pagination = document.querySelector('[data-pagination]');
    var page = 1;
    var lastPage = 1;
    var knownIds = null;
    var timer = null;
    var currentVisit = null;

    function actionUrl(template, id) {
        return template.replace('__ID__', id);
    }

    function dataUrl() {
        var params = new URLSearchParams(window.location.search);
        params.set('page', page);

        return table.dataset.url + '?' + params.toString();
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

    function waitingCell(row) {
        var wrap = el('span');
        wrap.appendChild(el('span', null, row.waiting_minutes + ' mnt'));

        if (row.waiting_long) {
            wrap.appendChild(document.createTextNode(' '));
            wrap.appendChild(el('span', 'badge bg-danger', 'Menunggu lama'));
        }

        return wrap;
    }

    function actionButtons(row) {
        var wrap = el('div', 'd-flex flex-wrap gap-1 justify-content-end justify-content-md-start');

        if (row.actions.assign) {
            var assign = el('button', 'btn btn-sm btn-primary', row.actions.assign_label);
            assign.type = 'button';
            assign.addEventListener('click', function () { openAssign(row); });
            wrap.appendChild(assign);
        }

        if (row.actions.cancel) {
            var cancel = el('button', 'btn btn-sm btn-outline-danger', 'Batal');
            cancel.type = 'button';
            cancel.addEventListener('click', function () { openCancel(row); });
            wrap.appendChild(cancel);
        }

        var detail = el('a', 'btn btn-sm btn-outline-gray-800', 'Detail');
        detail.href = row.detail_url;
        wrap.appendChild(detail);

        return wrap;
    }

    function renderRow(row) {
        var tr = el('tr');

        if (row.waiting_long) {
            tr.classList.add('row-waiting-long');
        }

        if (knownIds && !knownIds.has(row.id)) {
            tr.classList.add('row-new');
        }

        var assigned = row.assigned_department ? row.assigned_department + (row.served_by ? ' — ' + row.served_by : '') : null;
        var badge = el('span', 'badge bg-' + row.status_badge, row.status_label);

        tr.appendChild(cell('No.', 'No. ' + row.number, 'fw-bold'));
        tr.appendChild(cell('Nama', row.name, 'fw-bold'));
        tr.appendChild(cell('Instansi', row.organization));
        tr.appendChild(cell('Kelompok rentan', row.vulnerable_group));
        tr.appendChild(cell('Keperluan', row.category));
        tr.appendChild(cell('Saran departemen', row.suggested_department));
        tr.appendChild(cell('Departemen / ditemui', assigned));

        if (showLocation) {
            tr.appendChild(cell('Lokasi / QR', row.location + ' · ' + row.qr_label));
        }

        tr.appendChild(cell('Jam datang', row.check_in));
        tr.appendChild(cell('Menunggu', waitingCell(row)));
        tr.appendChild(cell('Status', badge));
        tr.appendChild(cell('Aksi', actionButtons(row)));

        return tr;
    }

    function render(data) {
        tbody.textContent = '';

        if (data.rows.length === 0) {
            var empty = el('tr');
            var td = el('td', 'text-center text-gray-600 py-4', 'Tidak ada tamu aktif.');
            td.colSpan = 12;
            empty.appendChild(td);
            tbody.appendChild(empty);
        }

        data.rows.forEach(function (row) {
            tbody.appendChild(renderRow(row));
        });

        knownIds = knownIds || new Set();
        data.rows.forEach(function (row) { knownIds.add(row.id); });

        Object.keys(data.counts).forEach(function (key) {
            var target = document.querySelector('[data-count="' + key + '"]');

            if (target) {
                target.textContent = data.counts[key];
            }
        });

        document.querySelector('[data-server-time]').textContent = data.server_time;

        lastPage = data.pagination.last_page;
        pagination.hidden = lastPage <= 1;
        pagination.querySelector('[data-page-info]').textContent = 'Halaman ' + data.pagination.current_page + ' dari ' + lastPage;
    }

    function load() {
        return SiTamu.request(dataUrl()).then(function (result) {
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

    // Polling berhenti saat tab tidak terlihat dan langsung berjalan saat tab aktif lagi.
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stopPolling();
        } else {
            load();
            startPolling();
        }
    });

    pagination.querySelectorAll('[data-page]').forEach(function (button) {
        button.addEventListener('click', function () {
            var next = button.dataset.page === 'next' ? page + 1 : page - 1;

            if (next >= 1 && next <= lastPage) {
                page = next;
                load();
            }
        });
    });

    function showModalError(form, message) {
        var box = form.querySelector('[data-modal-error]');
        box.textContent = message;
        box.classList.toggle('d-none', !message);
    }

    // Hasil aksi: sukses atau 409 memuat ulang daftar; 422 ditampilkan di modal.
    function handleAction(result, modal, form) {
        var body = result.body;

        if (body.ok) {
            modal.hide();
            SiTamu.toast(body.data.message, 'success');
            load();
            return;
        }

        if (result.status === 409) {
            modal.hide();
            SiTamu.toast(body.error.message, 'error');
            load();
            return;
        }

        showModalError(form, body.error ? body.error.message : 'Terjadi kesalahan. Silakan coba lagi.');
    }

    // Modal Tugaskan (RSP-2, RSP-3)
    var assignModalElement = document.getElementById('assignModal');
    var assignForm = document.querySelector('[data-assign-form]');
    var departmentSelect = document.getElementById('assign_department');
    var staffSelect = document.getElementById('assign_staff');
    var departments = [];

    function fillStaff(selectedStaffId) {
        staffSelect.textContent = '';
        staffSelect.appendChild(new Option('Pilih orang yang ditemui', ''));

        var department = departments.find(function (item) { return String(item.id) === departmentSelect.value; });

        (department ? department.staff : []).forEach(function (staff) {
            staffSelect.appendChild(new Option(staff.name, staff.id, false, staff.id === selectedStaffId));
        });
    }

    function openAssign(row) {
        currentVisit = row;
        showModalError(assignForm, '');
        assignForm.querySelector('[data-assign-guest]').textContent = 'No. ' + row.number + ' · ' + row.name + ' · ' + row.category;

        SiTamu.request(actionUrl(table.dataset.departmentsUrl, row.id)).then(function (result) {
            if (!result.body.ok) {
                SiTamu.toast(result.body.error.message, 'error');
                load();
                return;
            }

            var data = result.body.data;
            departments = data.departments;
            departmentSelect.textContent = '';
            departmentSelect.appendChild(new Option('Pilih departemen', ''));
            departments.forEach(function (department) {
                departmentSelect.appendChild(new Option(department.name, department.id, false, department.id === data.selected_department_id));
            });

            var suggestion = departments.find(function (item) { return item.id === data.suggested_department_id; });
            assignForm.querySelector('[data-assign-suggestion]').textContent = suggestion
                ? 'Saran dari kategori: ' + suggestion.name
                : (departments.length === 0 ? 'Belum ada departemen yang memiliki akun PIC dan petugas aktif.' : '');

            fillStaff(data.selected_staff_member_id);
            bootstrap.Modal.getOrCreateInstance(assignModalElement).show();
        });
    }

    if (assignForm) {
        departmentSelect.addEventListener('change', function () { fillStaff(null); });

        assignForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var submit = assignForm.querySelector('[type="submit"]');
            submit.disabled = true;

            SiTamu.request(actionUrl(table.dataset.assignUrl, currentVisit.id), 'POST', {
                department_id: departmentSelect.value,
                staff_member_id: staffSelect.value
            }).then(function (result) {
                handleAction(result, bootstrap.Modal.getOrCreateInstance(assignModalElement), assignForm);
            }).finally(function () {
                submit.disabled = false;
            });
        });
    }

    // Modal Batal (RSP-4, ADM-2)
    var cancelModalElement = document.getElementById('cancelModal');
    var cancelForm = document.querySelector('[data-cancel-form]');
    var reasonSelect = document.getElementById('cancel_reason');
    var reasonText = document.getElementById('cancel_reason_text');
    var otherWrap = cancelForm.querySelector('[data-cancel-other]');

    function openCancel(row) {
        currentVisit = row;
        cancelForm.reset();
        otherWrap.hidden = true;
        showModalError(cancelForm, '');
        cancelForm.querySelector('[data-cancel-guest]').textContent = 'No. ' + row.number + ' · ' + row.name;
        bootstrap.Modal.getOrCreateInstance(cancelModalElement).show();
    }

    reasonSelect.addEventListener('change', function () {
        otherWrap.hidden = reasonSelect.value !== 'other';
    });

    cancelForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var submit = cancelForm.querySelector('[type="submit"]');
        submit.disabled = true;

        SiTamu.request(actionUrl(table.dataset.cancelUrl, currentVisit.id), 'POST', {
            reason: reasonSelect.value,
            reason_text: reasonText.value
        }).then(function (result) {
            handleAction(result, bootstrap.Modal.getOrCreateInstance(cancelModalElement), cancelForm);
        }).finally(function () {
            submit.disabled = false;
        });
    });

    load();
    startPolling();
})();

/*
 * Dropdown yang bisa dicari (RSP-9): membungkus <select> asli tanpa library tambahan (A-3).
 * <select> tetap menjadi sumber nilai dan pemicu event change, sehingga kode pemanggil
 * cukup membaca select.value seperti biasa. Pola ARIA: combobox dengan listbox.
 */
window.SearchableSelect = (function () {
    var counter = 0;

    function normalize(text) {
        return String(text).toLowerCase().replace(/\s+/g, ' ').trim();
    }

    /**
     * Memasang pencarian pada satu <select>. Panggil refresh() setiap kali opsi select diganti.
     */
    function enhance(select) {
        var listId = 'searchable-list-' + (++counter);
        var wrapper = document.createElement('div');
        var input = document.createElement('input');
        var list = document.createElement('ul');
        var items = [];
        var activeIndex = -1;

        wrapper.className = 'searchable-select';
        input.type = 'text';
        input.id = select.id + '_search';
        input.className = 'form-select';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', listId);
        list.id = listId;
        list.className = 'searchable-select-list';
        list.setAttribute('role', 'listbox');
        list.hidden = true;

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(input);
        wrapper.appendChild(list);
        wrapper.appendChild(select);
        select.classList.add('searchable-select-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        // Label lama menunjuk ke select; arahkan ke input agar klik label memfokuskan kolom ketik.
        var label = document.querySelector('label[for="' + select.id + '"]');
        if (label) {
            label.htmlFor = input.id;
        }

        function selectedLabel() {
            var option = select.options[select.selectedIndex];

            return option && option.value !== '' ? option.text : '';
        }

        function isOpen() {
            return !list.hidden;
        }

        function setActive(index) {
            items.forEach(function (item, i) {
                item.classList.toggle('active', i === index);
            });
            activeIndex = index;

            if (index >= 0) {
                input.setAttribute('aria-activedescendant', items[index].id);
                items[index].scrollIntoView({ block: 'nearest' });
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function render(query) {
            var needle = normalize(query);
            list.textContent = '';
            items = [];

            Array.prototype.forEach.call(select.options, function (option) {
                if (option.value === '' || normalize(option.text).indexOf(needle) === -1) {
                    return;
                }

                var item = document.createElement('li');
                item.id = listId + '-' + items.length;
                item.className = 'searchable-select-option';
                item.setAttribute('role', 'option');
                item.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                item.dataset.value = option.value;
                item.textContent = option.text;
                list.appendChild(item);
                items.push(item);
            });

            if (items.length === 0) {
                var empty = document.createElement('li');
                empty.className = 'searchable-select-empty';
                empty.textContent = 'Tidak ada hasil';
                list.appendChild(empty);
            }

            var selectedIndex = items.findIndex(function (item) { return item.dataset.value === select.value; });
            setActive(needle === '' ? selectedIndex : (items.length > 0 ? 0 : -1));
        }

        function open() {
            if (input.disabled) {
                return;
            }

            render('');
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            // Teks ketikan yang tidak dipilih dibuang agar kolom selalu menampilkan pilihan sebenarnya.
            input.value = selectedLabel();
        }

        function choose(item) {
            if (select.value !== item.dataset.value) {
                select.value = item.dataset.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            input.classList.remove('is-invalid');
            close();
        }

        input.addEventListener('focus', function () {
            input.select();
        });

        input.addEventListener('click', function () {
            if (!isOpen()) {
                open();
            }
        });

        input.addEventListener('input', function () {
            if (!isOpen()) {
                list.hidden = false;
                input.setAttribute('aria-expanded', 'true');
            }

            render(input.value);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();

                if (!isOpen()) {
                    open();
                    return;
                }

                if (items.length > 0) {
                    var step = event.key === 'ArrowDown' ? 1 : -1;
                    setActive((activeIndex + step + items.length) % items.length);
                }
            } else if (event.key === 'Enter' && isOpen()) {
                // Enter memilih opsi, bukan mengirim form.
                event.preventDefault();

                if (activeIndex >= 0) {
                    choose(items[activeIndex]);
                }
            } else if (event.key === 'Escape' && isOpen()) {
                // Hanya menutup daftar, bukan modal di sekitarnya.
                event.stopPropagation();
                close();
            } else if (event.key === 'Tab' && isOpen()) {
                close();
            }
        });

        // mousedown dicegah agar input tidak kehilangan fokus sebelum klik opsi diproses.
        list.addEventListener('mousedown', function (event) {
            event.preventDefault();
        });

        list.addEventListener('click', function (event) {
            var item = event.target.closest('.searchable-select-option');

            if (item) {
                choose(item);
            }
        });

        input.addEventListener('blur', function () {
            if (isOpen()) {
                close();
            }
        });

        // Validasi bawaan browser tetap berjalan pada select yang tersembunyi; tandai kolom ketiknya.
        select.addEventListener('invalid', function () {
            input.classList.add('is-invalid');
            input.focus();
        });

        /**
         * Menyamakan kolom ketik dengan isi, nilai, dan status nonaktif select terkini.
         */
        function refresh() {
            var placeholder = Array.prototype.find.call(select.options, function (option) { return option.value === ''; });

            input.placeholder = placeholder ? placeholder.text : '';
            input.disabled = select.disabled;
            input.classList.remove('is-invalid');

            if (isOpen()) {
                list.hidden = true;
                input.setAttribute('aria-expanded', 'false');
            }

            input.value = selectedLabel();
        }

        refresh();

        return { refresh: refresh };
    }

    return { enhance: enhance };
})();

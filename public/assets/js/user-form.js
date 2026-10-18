/*
 * Form akun staf: menampilkan field sesuai peran terpilih (USR-1).
 * Server tetap membuang field yang tidak sesuai peran.
 */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-user-form]');

    if (!form) {
        return;
    }

    var roleSelect = form.querySelector('[data-role-select]');
    var sections = form.querySelectorAll('[data-role-section]');

    function showSectionsForRole() {
        sections.forEach(function (section) {
            section.hidden = section.dataset.roleSection !== roleSelect.value;
        });
    }

    roleSelect.addEventListener('change', showSectionsForRole);
    showSectionsForRole();
});

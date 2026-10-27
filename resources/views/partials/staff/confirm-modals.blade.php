{{-- Modal konfirmasi global untuk semua form staf: Simpan, Batal, Nonaktifkan, dan aksi data-confirm. --}}
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title h6 mb-0" id="confirmSaveModalTitle">Konfirmasi Simpan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-gray-700">Simpan perubahan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Kembali</button>
                <button type="button" class="btn btn-primary" id="confirmSaveSubmitBtn">Ya, Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="confirmCancelModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title h6 mb-0" id="confirmCancelModalTitle">Konfirmasi Pembatalan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-gray-700">Batalkan perubahan? Perubahan yang belum disimpan akan hilang.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Tetap di Sini</button>
                <button type="button" class="btn btn-danger" id="confirmCancelActionBtn">Ya, Batalkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeactivateModal" tabindex="-1" aria-labelledby="confirmDeactivateModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title h6 mb-0" id="confirmDeactivateModalTitle">Konfirmasi Penonaktifan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-gray-700" id="confirmDeactivateModalMessage">Nonaktifkan akun ini? Akun ini tidak bisa digunakan sampai diaktifkan kembali.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDeactivateSubmitBtn">Ya, Nonaktifkan</button>
            </div>
        </div>
    </div>
</div>

{{-- Dipakai form ber-atribut data-confirm (misalnya Hapus lokasi, Regenerate QR); isi diatur app.js. --}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h2 class="modal-title h6 mb-0" id="confirmActionModalTitle">Konfirmasi</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-gray-700" id="confirmActionModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmActionSubmitBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

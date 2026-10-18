{{-- Modal alasan pembatalan kunjungan (RSP-4, ADM-2). --}}
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" data-cancel-form>
            <div class="modal-header">
                <h2 class="modal-title h6" id="cancelModalTitle">Batalkan kunjungan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-gray-600" data-cancel-guest></p>
                <div class="mb-3">
                    <label for="cancel_reason" class="form-label">Alasan</label>
                    <select id="cancel_reason" name="reason" class="form-select" required>
                        <option value="">Pilih alasan</option>
                        @foreach ($cancelReasons as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2" data-cancel-other hidden>
                    <label for="cancel_reason_text" class="form-label">Keterangan</label>
                    <textarea id="cancel_reason_text" name="reason_text" class="form-control" rows="2" maxlength="255"></textarea>
                </div>
                <div class="alert alert-danger small d-none mb-0" role="alert" data-modal-error></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Kembali</button>
                <button type="submit" class="btn btn-danger">Batalkan Kunjungan</button>
            </div>
        </form>
    </div>
</div>

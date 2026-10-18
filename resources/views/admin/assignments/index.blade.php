@extends('layouts.staff')

@section('title', 'Penugasan')

@section('content')
    @if ($picWithoutDepartment)
        <div class="alert alert-warning" role="alert">Akun ini belum dihubungkan ke departemen.</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3 small">
        <span class="text-gray-600">Daftar diperbarui otomatis setiap {{ config('sitamu.poll_interval_seconds') }} detik.</span>
        <span class="text-gray-500">Diperbarui <span data-server-time>–</span></span>
    </div>

    <div data-assignments
         data-url="{{ route('admin.assignments.data') }}"
         data-poll="{{ config('sitamu.poll_interval_seconds') }}"
         data-accept-url="{{ url('/admin/kunjungan/__ID__/terima') }}"
         data-complete-url="{{ url('/admin/kunjungan/__ID__/selesai') }}">
        @foreach (['new' => 'Penugasan baru', 'reviewing' => 'Sedang direview'] as $group => $heading)
            <section class="card card-body border-0 shadow table-wrapper table-responsive mb-4">
                <h2 class="h6 mb-3">{{ $heading }} <span class="badge bg-gray-200 text-gray-800" data-group-count="{{ $group }}">0</span></h2>
                <table class="table table-hover align-middle mb-0 table-cards">
                    <thead>
                        <tr>
                            <th class="border-gray-200">No.</th>
                            <th class="border-gray-200">Nama</th>
                            <th class="border-gray-200">Instansi</th>
                            <th class="border-gray-200">Kelompok rentan</th>
                            <th class="border-gray-200">Keperluan</th>
                            <th class="border-gray-200">Orang yang ditemui</th>
                            <th class="border-gray-200">Lokasi</th>
                            <th class="border-gray-200">Jam datang</th>
                            <th class="border-gray-200">Menunggu</th>
                            <th class="border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody data-group="{{ $group }}">
                        <tr>
                            <td colspan="10" class="text-center text-gray-600 py-4">Memuat data…</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        @endforeach
    </div>

    <div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalTitle" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" data-complete-form>
                <div class="modal-header">
                    <h2 class="modal-title h6" id="completeModalTitle">Selesaikan kunjungan</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-gray-600" data-complete-guest></p>
                    <label for="complete_result" class="form-label">Hasil</label>
                    <textarea id="complete_result" name="result" class="form-control" rows="5" maxlength="2000" required
                              aria-describedby="complete_result_help"></textarea>
                    <div class="form-text" id="complete_result_help">Hasil ini akan terlihat oleh tamu.</div>
                    <div class="alert alert-danger small d-none mt-2 mb-0" role="alert" data-modal-error></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan dan Selesai</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/assignments.js') }}"></script>
@endpush

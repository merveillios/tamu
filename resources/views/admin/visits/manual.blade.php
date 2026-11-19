@extends('layouts.staff')

@section('title', 'Tambah Tamu Manual')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-8">
            @if ($qrCodes->isEmpty())
                <div class="alert alert-warning" role="alert">
                    Belum ada pos (QR) aktif milik akun ini yang dapat dipakai. Hubungi Admin.
                </div>
            @else
                <form method="POST" action="{{ route('admin.visits.manual.store') }}" enctype="multipart/form-data"
                      class="card card-body border-0 shadow mb-4" data-manual-visit>
                    @csrf

                    <p class="small text-gray-600">
                        Untuk tamu yang tidak bisa login Google. Kunjungan masuk Tamu Aktif dengan tanda <strong>Manual</strong>
                        dan ditugaskan seperti biasa. Data diri diketik ulang setiap kunjungan.
                    </p>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                    @endif

                    @if ($qrCodes->count() > 1)
                        <div class="mb-3">
                            <label for="qr_code_id" class="form-label">Pos (QR)</label>
                            <select name="qr_code_id" id="qr_code_id" required class="form-select @error('qr_code_id') is-invalid @enderror">
                                @foreach ($qrCodes as $qr)
                                    <option value="{{ $qr->id }}" @selected((int) old('qr_code_id') === $qr->id)>
                                        {{ $qr->label ?? 'QR #'.$qr->id }} — {{ $qr->location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        @php($onlyQr = $qrCodes->first())
                        <input type="hidden" name="qr_code_id" value="{{ $onlyQr->id }}">
                        <p class="small mb-3">Pos: <strong>{{ $onlyQr->label ?? 'QR #'.$onlyQr->id }} — {{ $onlyQr->location->name }}</strong></p>
                    @endif

                    <div class="mb-3">
                        <label for="email" class="form-label">Email tamu</label>
                        <input type="email" name="email" id="email" maxlength="254" required autocomplete="off" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror" aria-describedby="email_help email_error">
                        <div class="form-text" id="email_help">Tanyakan email tamu. Bila suatu saat tamu login Google dengan email ini, kunjungan ini tampil di riwayatnya.</div>
                        @error('email')
                            <div class="invalid-feedback" id="email_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="purpose" class="form-label">Rincian keperluan</label>
                        <textarea name="purpose" id="purpose" rows="3" minlength="3" maxlength="500" required
                                  class="form-control @error('purpose') is-invalid @enderror" aria-describedby="purpose_error">{{ old('purpose') }}</textarea>
                        @error('purpose')
                            <div class="invalid-feedback" id="purpose_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="vulnerable_group" class="form-label">Kelompok rentan</label>
                        <select name="vulnerable_group" id="vulnerable_group" required
                                class="form-select @error('vulnerable_group') is-invalid @enderror" aria-describedby="vulnerable_group_error">
                            <option value="">Pilih kelompok rentan</option>
                            @foreach ($vulnerableGroups as $group)
                                <option value="{{ $group->code }}" @selected(old('vulnerable_group') === $group->code)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('vulnerable_group')
                            <div class="invalid-feedback" id="vulnerable_group_error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="name" class="form-label">Nama lengkap</label>
                            <input type="text" name="name" id="name" maxlength="100" required autocomplete="off" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                            @error('name')
                                <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="organization" class="form-label">Instansi/perusahaan</label>
                            <input type="text" name="organization" id="organization" maxlength="150" required autocomplete="off" value="{{ old('organization') }}"
                                   class="form-control @error('organization') is-invalid @enderror" aria-describedby="organization_help organization_error">
                            <div class="form-text" id="organization_help">Tulis Pribadi jika datang atas nama pribadi.</div>
                            @error('organization')
                                <div class="invalid-feedback" id="organization_error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label">Nomor telepon/WhatsApp</label>
                            <input type="text" name="phone" id="phone" maxlength="30" required inputmode="tel" autocomplete="off" value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror" aria-describedby="phone_error">
                            @error('phone')
                                <div class="invalid-feedback" id="phone_error">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="address" class="form-label">Alamat</label>
                            <textarea name="address" id="address" rows="2" maxlength="255" required
                                      class="form-control @error('address') is-invalid @enderror" aria-describedby="address_error">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback" id="address_error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        @foreach (\App\Models\GuestDocument::TYPES as $type => $label)
                            <div class="col-12 col-md-6" data-photo-field data-photo-type="{{ $type }}">
                                <div class="form-label">{{ $label }} <span class="text-gray-600 fw-normal">(opsional)</span></div>
                                <input type="hidden" name="{{ $type }}_data" data-photo-data>
                                <img alt="Pratinjau {{ $label }}" class="img-fluid rounded border bg-white mb-2 guest-document-preview" data-photo-preview hidden>
                                @include('partials.photo-sources', ['type' => $type, 'name' => $type.'_photo', 'id' => $type.'_photo', 'invalid' => $errors->has($type.'_photo')])
                                <div class="small text-gray-600 mt-1" data-photo-status aria-live="polite">
                                    {{ $type === 'ktp' ? 'Pastikan tulisan KTP terbaca.' : 'Wajah tamu terlihat jelas.' }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="signature_canvas">Tanda tangan tamu</label>
                        <canvas id="signature_canvas" class="signature-canvas @error('signature') is-invalid @enderror"
                                data-signature-canvas aria-describedby="signature_help signature_error"></canvas>
                        <input type="hidden" name="signature" data-signature-input>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="small text-gray-600" id="signature_help">Minta tamu menandatangani di layar ini.</span>
                            <button type="button" class="btn btn-sm btn-outline-gray-800" data-signature-clear>Hapus Tanda Tangan</button>
                        </div>
                        @if ($errors->any())
                            <div class="small text-danger mt-1" id="signature_error">Minta tamu membubuhkan tanda tangan kembali.</div>
                        @endif
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" value="1" id="consent" required>
                        <label class="form-check-label" for="consent">
                            Tamu sudah membaca dan menyetujui: “{{ config('consent.text') }}”
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" data-photo-submit>Simpan Kunjungan</button>
                        <a href="{{ route('admin.visits.active') }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/signature-pad.js') }}?v={{ filemtime(public_path('assets/js/signature-pad.js')) }}"></script>
    <script src="{{ asset('assets/js/photo-field.js') }}?v={{ filemtime(public_path('assets/js/photo-field.js')) }}"></script>
@endpush

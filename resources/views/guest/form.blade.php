@extends('layouts.guest')

@section('title', 'Formulir Buku Tamu')

@php
    $locked = $profile !== null;
    $value = fn (string $field) => $locked ? $profile[$field] : old($field);
@endphp

@section('content')
    @include('guest.partials.email-block')

    <p class="small text-gray-600 mb-3">Jam datang dicatat otomatis saat formulir dikirim.</p>

    @if ($errors->any())
        <div class="alert alert-danger small" role="alert">Periksa kembali isian yang ditandai merah.</div>
    @endif

    <form method="POST" action="{{ route('guest.store', $qr->token) }}" class="card border-0 shadow" data-guest-form novalidate>
        @csrf
        <input type="hidden" name="form_token" value="{{ $formToken }}">
        {{-- Honeypot anti-bot (TAM-8): disembunyikan dengan CSS, manusia tidak mengisinya. --}}
        <div class="guest-hp" aria-hidden="true">
            <label for="website">Website</label>
            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="card-body p-4">
            <div class="mb-3">
                <label for="purpose_category_id" class="form-label">Keperluan</label>
                <select name="purpose_category_id" id="purpose_category_id" required data-purpose-category
                        class="form-select @error('purpose_category_id') is-invalid @enderror" aria-describedby="purpose_category_id_error">
                    <option value="">Pilih keperluan</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('purpose_category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('purpose_category_id')
                    <div class="invalid-feedback" id="purpose_category_id_error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3" data-purpose-detail @if (! old('purpose_category_id')) hidden @endif>
                <label for="purpose" class="form-label">Rincian keperluan</label>
                <textarea name="purpose" id="purpose" rows="3" maxlength="500" required
                          placeholder="Contoh: Ingin konsultasi terkait perizinan perdagangan"
                          class="form-control @error('purpose') is-invalid @enderror" aria-describedby="purpose_error">{{ old('purpose') }}</textarea>
                @error('purpose')
                    <div class="invalid-feedback" id="purpose_error">{{ $message }}</div>
                @enderror
            </div>

            @if ($locked)
                <p class="small text-gray-600 border-top pt-3">Data diri diambil dari kunjungan terakhir Anda.</p>
            @endif

            <div class="mb-3">
                <label for="vulnerable_group" class="form-label">Kelompok rentan</label>
                <select name="vulnerable_group" id="vulnerable_group" required @disabled($locked)
                        class="form-select @error('vulnerable_group') is-invalid @enderror" aria-describedby="vulnerable_group_error">
                    <option value="">Pilih kelompok rentan</option>
                    @foreach ($vulnerableGroups as $group)
                        <option value="{{ $group->value }}" @selected($value('vulnerable_group') === $group->value)>{{ $group->label() }}</option>
                    @endforeach
                </select>
                @error('vulnerable_group')
                    <div class="invalid-feedback" id="vulnerable_group_error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nama lengkap</label>
                <input type="text" name="name" id="name" maxlength="100" required autocomplete="name" @disabled($locked)
                       value="{{ $value('name') }}" class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                @error('name')
                    <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="organization" class="form-label">Instansi/perusahaan</label>
                <input type="text" name="organization" id="organization" maxlength="150" required autocomplete="organization" @disabled($locked)
                       value="{{ $value('organization') }}" class="form-control @error('organization') is-invalid @enderror"
                       aria-describedby="organization_help organization_error">
                <div class="form-text" id="organization_help">Tulis Pribadi jika datang atas nama pribadi.</div>
                @error('organization')
                    <div class="invalid-feedback" id="organization_error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Nomor telepon/WhatsApp</label>
                <input type="text" name="phone" id="phone" maxlength="30" required inputmode="tel" autocomplete="tel" @disabled($locked)
                       value="{{ $locked ? \App\Rules\IndonesianPhone::display($profile['phone']) : old('phone') }}"
                       class="form-control @error('phone') is-invalid @enderror" aria-describedby="phone_error">
                @error('phone')
                    <div class="invalid-feedback" id="phone_error">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Alamat</label>
                <textarea name="address" id="address" rows="2" maxlength="255" required autocomplete="street-address" @disabled($locked)
                          placeholder="Contoh: Jl. Ahmad Yani No. 1, Surabaya"
                          class="form-control @error('address') is-invalid @enderror" aria-describedby="address_error">{{ $value('address') }}</textarea>
                @error('address')
                    <div class="invalid-feedback" id="address_error">{{ $message }}</div>
                @enderror
            </div>

            @if ($needsSignature)
                <div class="mb-3">
                    <label class="form-label" for="signature_canvas">Tanda tangan</label>
                    <canvas id="signature_canvas" class="signature-canvas @error('signature') is-invalid @enderror"
                            data-signature-canvas aria-describedby="signature_help signature_error"></canvas>
                    <input type="hidden" name="signature" data-signature-input>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="small text-gray-600" id="signature_help">Gunakan jari, stylus, atau mouse. Cukup sekali; kunjungan berikutnya tidak perlu tanda tangan lagi.</span>
                        <button type="button" class="btn btn-sm btn-outline-gray-800" data-signature-clear>Hapus Tanda Tangan</button>
                    </div>
                    @if ($errors->has('signature'))
                        <div class="small text-danger mt-1" id="signature_error">{{ $errors->first('signature') }}</div>
                    @elseif ($errors->any())
                        <div class="small text-danger mt-1" id="signature_error">Silakan bubuhkan tanda tangan kembali.</div>
                    @endif
                </div>
            @else
                <p class="small text-gray-600 mb-3">Tanda tangan diambil dari kunjungan pertama Anda.</p>
            @endif

            <div class="form-check mb-4">
                <input class="form-check-input @error('consent') is-invalid @enderror" type="checkbox" name="consent" value="1" id="consent" required
                       aria-describedby="consent_error">
                <label class="form-check-label" for="consent">{{ config('consent.text') }}</label>
                @error('consent')
                    <div class="invalid-feedback" id="consent_error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100" data-submit-once>Kirim Formulir</button>
        </div>
    </form>
@endsection

@push('scripts')
    @if ($needsSignature)
        <script src="{{ asset('assets/js/signature-pad.js') }}"></script>
    @endif
    <script src="{{ asset('assets/js/guest-form.js') }}"></script>
@endpush

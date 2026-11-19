{{-- "Lengkapi profil": foto KTP dan selfie milik tamu (TAM-18, revisi HRD #4). --}}
<section class="card border-0 shadow-sm mb-4" aria-labelledby="profile-heading">
    <div class="card-body">
        <h2 id="profile-heading" class="h6 mb-1">Lengkapi profil <span class="fw-normal text-gray-600">(opsional)</span></h2>
        <p class="small text-gray-600 mb-3">
            Foto KTP &amp; selfie digunakan petugas Disperindag untuk dokumentasi kunjungan.
        </p>

        @foreach (\App\Models\GuestDocument::TYPES as $type => $label)
            @php($document = $documents->get($type))
            <form method="POST" action="{{ route('guest.documents.store', [$qr->token, $type]) }}" enctype="multipart/form-data"
                  class="border rounded p-3 mb-3" data-photo-required>
                @csrf
                <div data-photo-field data-photo-type="{{ $type }}">
                <input type="hidden" name="image_data" data-photo-data>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold small">{{ $label }}</span>
                    @if ($document)
                        <span class="badge bg-success">Tersimpan {{ $document->created_at->format('d/m/Y') }}</span>
                    @else
                        <span class="badge bg-gray-200 text-gray-800">Belum ada</span>
                    @endif
                </div>

                <img @if ($document) src="{{ route('guest.documents.show', [$qr->token, $type]) }}" @endif
                     alt="{{ $label }} Anda" class="img-fluid rounded border bg-white mb-2 guest-document-preview"
                     data-photo-preview @unless ($document) hidden @endunless>

                <div class="small mb-1">
                    {{ $document ? 'Ganti foto' : 'Pilih foto' }}
                    @if ($type === 'ktp')
                        <span class="text-gray-600">(pastikan tulisan terbaca)</span>
                    @else
                        <span class="text-gray-600">(wajah terlihat jelas)</span>
                    @endif
                </div>
                @include('partials.photo-sources', ['type' => $type, 'name' => 'photo', 'id' => 'photo_'.$type])
                <div class="small text-gray-600 mb-2" data-photo-status aria-live="polite"></div>
                </div>

                <button type="submit" class="btn btn-sm btn-primary w-100" data-photo-submit>
                    {{ $document ? 'Simpan foto baru' : 'Simpan foto' }}
                </button>
            </form>
        @endforeach
    </div>
</section>

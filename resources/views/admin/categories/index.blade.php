@extends('layouts.staff')

@section('title', 'Pertanyaan')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($location === null)
        <div class="alert alert-info" role="alert">Belum ada lokasi. Super Admin perlu membuat lokasi terlebih dahulu.</div>
    @else
        <div class="card card-body border-0 shadow mb-3">
            <div class="row g-2 align-items-end">
                @if ($locations->count() > 1)
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="col-12 col-md-8 d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label for="location_id" class="form-label small mb-1">Lokasi</label>
                            <select name="location_id" id="location_id" class="form-select">
                                @foreach ($locations as $option)
                                    <option value="{{ $option->id }}" @selected($option->id === $location->id)>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-gray-800">Tampilkan</button>
                    </form>
                @else
                    <div class="col-12 col-md-8"><span class="fw-bold">{{ $location->name }}</span></div>
                @endif
                <p class="col-12 small text-gray-600 mb-0">Tekan departemen untuk melihat dan menambah pertanyaannya.</p>
            </div>
        </div>

        @if ($sections->every(fn ($section) => $section['categories']->isEmpty()))
            <div class="alert alert-info" role="alert">
                Belum ada pertanyaan. Formulir tamu di lokasi ini baru bisa dibuka setelah ada minimal satu pertanyaan aktif.
            </div>
        @endif

        {{-- Tanpa data-bs-parent agar beberapa departemen bisa terbuka bersamaan. --}}
        <div class="accordion" id="question-sections">
            @foreach ($sections as $section)
                @php($open = $section['key'] === $openSection)
                <div class="accordion-item border-0 shadow mb-2">
                    <h2 class="accordion-header" id="section-{{ $section['key'] }}-heading">
                        <button type="button" class="accordion-button {{ $open ? '' : 'collapsed' }}" data-bs-toggle="collapse"
                                data-bs-target="#section-{{ $section['key'] }}" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="section-{{ $section['key'] }}">
                            <span class="fw-bold me-auto">{{ $section['department']?->name ?? 'Umum (tanpa departemen)' }}</span>
                            <span class="small text-gray-600 me-3">{{ $section['categories']->count() }} pertanyaan</span>
                        </button>
                    </h2>
                    <div id="section-{{ $section['key'] }}" class="accordion-collapse collapse {{ $open ? 'show' : '' }}" aria-labelledby="section-{{ $section['key'] }}-heading">
                        <div class="accordion-body">
                            @if ($section['categories']->isEmpty())
                                <p class="text-gray-600">Belum ada pertanyaan.</p>
                            @else
                                <div class="table-responsive mb-3">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="border-gray-200">Pertanyaan</th>
                                                <th class="border-gray-200">Urutan</th>
                                                <th class="border-gray-200">Status</th>
                                                <th class="border-gray-200">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($section['categories'] as $category)
                                                <tr>
                                                    <td class="text-wrap">
                                                        <span class="fw-bold">{{ $category->name }}</span>
                                                        {{-- Di bagian Umum, departemen yang masih tercatat berarti sudah nonaktif. --}}
                                                        @if ($section['department'] === null && $category->suggestedDepartment !== null)
                                                            <div class="small text-gray-500">Departemen {{ $category->suggestedDepartment->name }} nonaktif</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $category->sort_order }}</td>
                                                    <td>
                                                        <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                                                            <form method="POST" action="{{ route('admin.categories.status', $category->id) }}">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm {{ $category->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                                    {{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                            <a href="{{ route('admin.categories.create', ['location_id' => $location->id, 'departemen' => $section['key']]) }}" class="btn btn-sm btn-primary">
                                Tambah pertanyaan
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

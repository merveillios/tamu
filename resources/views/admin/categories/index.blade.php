@extends('layouts.staff')

@section('title', 'Pertanyaan')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($location === null)
        <div class="alert alert-info" role="alert">Belum ada lokasi. Super Admin perlu membuat lokasi terlebih dahulu.</div>
    @else
        @if ($section === null)
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
                </div>
            </div>

            @if ($sections->every(fn ($item) => $item['categories']->isEmpty()))
                <div class="alert alert-info" role="alert">
                    Belum ada pertanyaan. Formulir tamu di lokasi ini baru bisa dibuka setelah ada minimal satu pertanyaan aktif.
                </div>
            @endif

            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="border-gray-200">Departemen</th>
                            <th class="border-gray-200">Jumlah pertanyaan</th>
                            <th class="border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $item)
                            <tr>
                                <td class="fw-bold">{{ $item['department']?->name ?? 'Umum (tanpa departemen)' }}</td>
                                <td>{{ $item['categories']->count() }}</td>
                                <td>
                                    <a href="{{ route('admin.categories.index', ['location_id' => $location->id, 'departemen' => $item['key']]) }}" class="btn btn-sm btn-outline-primary">
                                        Lihat pertanyaan
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card card-body border-0 shadow mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-8">
                        <p class="small text-gray-600 mb-1">Lokasi: {{ $location->name }}</p>
                        <h2 class="h5 mb-0">{{ $section['department']?->name ?? 'Umum (tanpa departemen)' }}</h2>
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <a href="{{ route('admin.categories.index', ['location_id' => $location->id]) }}" class="btn btn-gray-200 flex-fill">Kembali</a>
                        <a href="{{ route('admin.categories.create', ['location_id' => $location->id, 'departemen' => $section['key']]) }}" class="btn btn-primary flex-fill">Tambah Pertanyaan</a>
                    </div>
                </div>
            </div>

            <div class="card card-body border-0 shadow table-wrapper table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="border-gray-200">Urutan</th>
                            <th class="border-gray-200">Pertanyaan</th>
                            <th class="border-gray-200">Status</th>
                            <th class="border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($section['categories'] as $category)
                            <tr>
                                <td>{{ $category->sort_order }}</td>
                                <td class="text-wrap">
                                    <span class="fw-bold">{{ $category->name }}</span>
                                    {{-- Di bagian Umum, departemen yang masih tercatat berarti sudah nonaktif. --}}
                                    @if ($section['department'] === null && $category->suggestedDepartment !== null)
                                        <div class="small text-gray-500">Departemen {{ $category->suggestedDepartment->name }} nonaktif</div>
                                    @endif
                                </td>
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
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-600 py-4">Belum ada pertanyaan di departemen ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    @endif
@endsection

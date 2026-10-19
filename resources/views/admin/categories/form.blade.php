@extends('layouts.staff')

@section('title', $category->exists ? 'Ubah Pertanyaan' : 'Tambah Pertanyaan')

@section('content')
    <div class="row">
        <div class="col-12 col-xl-6">
            <div class="card card-body border-0 shadow mb-4">
                <h2 class="h5 mb-1">{{ $category->exists ? 'Ubah pertanyaan' : 'Pertanyaan baru' }}</h2>
                <p class="small text-gray-600 mb-4">Lokasi: {{ $location->name }}</p>

                <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category->id) : route('admin.categories.store', ['location_id' => $location->id]) }}">
                    @csrf

                    <div class="mb-3">
                        @if ($fixedDepartment !== null)
                            {{-- Ditambah dari halaman departemen: departemen sudah ditentukan dan tidak dipilih lagi. --}}
                            <label for="department_name" class="form-label">Departemen</label>
                            <input type="text" id="department_name" value="{{ $fixedDepartment->name }}" class="form-control" readonly>
                            <input type="hidden" name="suggested_department_id" value="{{ $fixedDepartment->id }}">
                        @else
                            <label for="suggested_department_id" class="form-label">Departemen</label>
                            <select name="suggested_department_id" id="suggested_department_id" required
                                    class="form-select @error('suggested_department_id') is-invalid @enderror" aria-describedby="suggested_department_id_error">
                                <option value="">Pilih departemen</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected((int) old('suggested_department_id', $category->suggested_department_id) === $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('suggested_department_id')
                            <div class="invalid-feedback d-block" id="suggested_department_id_error">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Di formulir tamu, pertanyaan tampil di bawah nama departemen ini. Departemen ini juga dipilih otomatis sebagai saran saat resepsionis menugaskan kunjungan.</div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Pertanyaan</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required maxlength="150"
                               class="form-control @error('name') is-invalid @enderror" aria-describedby="name_error">
                        @error('name')
                            <div class="invalid-feedback" id="name_error">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Pilihan yang dilihat tamu di formulir. Contoh: Bagaimana cara mengurus izin usaha industri?</div>
                    </div>

                    <div class="mb-4">
                        <label for="sort_order" class="form-label">Urutan tampil</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" min="0" max="999"
                               class="form-control @error('sort_order') is-invalid @enderror" aria-describedby="sort_order_error">
                        @error('sort_order')
                            <div class="invalid-feedback" id="sort_order_error">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Angka kecil tampil lebih dulu di dalam grup departemennya.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="{{ $backUrl }}" class="btn btn-gray-200">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

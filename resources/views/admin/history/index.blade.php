@extends('layouts.staff')

@section('title', 'History')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.history.index') }}" class="card card-body border-0 shadow mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="q" class="form-label small mb-1">Cari nama, instansi, atau rincian keperluan</label>
                <input type="search" name="q" id="q" class="form-control" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-2">
                <label for="period" class="form-label small mb-1">Periode</label>
                <select name="period" id="period" class="form-select">
                    <option value="">Rentang tanggal</option>
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['period'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="from" class="form-label small mb-1">Dari tanggal</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-3">
                <label for="to" class="form-label small mb-1">Sampai tanggal</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-2">
                <label for="status" class="form-label small mb-1">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Selesai & Dibatalkan</option>
                    @foreach (['selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan', 'direview' => 'Sedang direview'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($locations->count() > 1)
                <div class="col-6 col-lg-2">
                    <label for="location_id" class="form-label small mb-1">Lokasi</label>
                    <select name="location_id" id="location_id" class="form-select">
                        <option value="">Semua lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((int) ($filters['location_id'] ?? 0) === $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-lg-3">
                <label for="purpose_category_id" class="form-label small mb-1">Kategori keperluan</label>
                <select name="purpose_category_id" id="purpose_category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) ($filters['purpose_category_id'] ?? 0) === $category->id)>
                            {{ $category->name }}{{ $locations->count() > 1 ? ' — '.$category->location->name : '' }}{{ $category->is_active ? '' : ' (nonaktif)' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3">
                <label for="department_id" class="form-label small mb-1">Departemen tujuan</label>
                <select name="department_id" id="department_id" class="form-select">
                    <option value="">Semua departemen</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === $department->id)>
                            {{ $department->name }}{{ $locations->count() > 1 ? ' — '.$department->location->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label for="vulnerable_group" class="form-label small mb-1">Kelompok rentan</label>
                <select name="vulnerable_group" id="vulnerable_group" class="form-select">
                    <option value="">Semua</option>
                    @foreach ($vulnerableGroups as $group)
                        <option value="{{ $group->value }}" @selected(($filters['vulnerable_group'] ?? '') === $group->value)>{{ $group->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-gray-800 flex-grow-1">Terapkan</button>
                <a href="{{ route('admin.history.index') }}" class="btn btn-gray-200">Reset</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small text-gray-600">{{ $visits->total() }} kunjungan</span>
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Ekspor</button>
            <ul class="dropdown-menu dropdown-menu-end">
                @foreach (['csv' => 'CSV', 'xlsx' => 'Excel (.xlsx)', 'pdf' => 'PDF'] as $format => $label)
                    <li><a class="dropdown-item" href="{{ route('admin.history.export', array_merge($filters, ['format' => $format])) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card card-body border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards">
                <thead>
                    <tr>
                        <th class="border-gray-200">Tanggal</th>
                        <th class="border-gray-200">No.</th>
                        <th class="border-gray-200">Nama</th>
                        <th class="border-gray-200">Instansi</th>
                        <th class="border-gray-200">Kelompok rentan</th>
                        <th class="border-gray-200">Kategori</th>
                        <th class="border-gray-200">Departemen tujuan</th>
                        <th class="border-gray-200">Lokasi</th>
                        <th class="border-gray-200">Jam datang</th>
                        <th class="border-gray-200">Status</th>
                        <th class="border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td data-label="Tanggal">{{ $visit->visit_date->format('d/m/Y') }}</td>
                            <td data-label="No.">No. {{ $visit->daily_number }}</td>
                            <td data-label="Nama" class="fw-bold">{{ $visit->name }}</td>
                            <td data-label="Instansi">{{ $visit->organization }}</td>
                            <td data-label="Kelompok rentan">{{ $visit->vulnerable_group->label() }}</td>
                            <td data-label="Kategori">{{ $visit->purpose_category_name }}</td>
                            <td data-label="Departemen">{{ $visit->assignedDepartment?->name ?? '–' }}</td>
                            <td data-label="Lokasi">{{ $visit->location->name }}</td>
                            <td data-label="Jam datang">{{ $visit->check_in_at->format('H:i') }}</td>
                            <td data-label="Status"><span class="badge bg-{{ $visit->display_status['badge'] }}">{{ $visit->display_status['label'] }}</span></td>
                            <td data-label="Aksi"><a href="{{ route('admin.visits.show', $visit->id) }}" class="btn btn-sm btn-outline-gray-800">Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-gray-600 py-4">Tidak ada kunjungan yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $visits->links() }}</div>
    </div>
@endsection

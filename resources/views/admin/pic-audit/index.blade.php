@extends('layouts.staff')

@section('title', 'Audit Log PIC')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.pic-audit.index') }}" class="card card-body border-0 shadow mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-lg-3">
                <label for="q" class="form-label small mb-1">Cari nama tamu atau instansi</label>
                <input type="search" name="q" id="q" class="form-control" value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-6 col-lg-3">
                <label for="staff_member_id" class="form-label small mb-1">PIC</label>
                <select name="staff_member_id" id="staff_member_id" class="form-select">
                    <option value="">Semua PIC</option>
                    @foreach ($staffMembers as $departmentName => $group)
                        <optgroup label="{{ $departmentName }}">
                            @foreach ($group as $staff)
                                <option value="{{ $staff->id }}" @selected((int) ($filters['staff_member_id'] ?? 0) === $staff->id)>
                                    {{ $staff->name }}@unless ($staff->is_active) (nonaktif)@endunless
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            @unless ($isPic)
            <div class="col-6 col-lg-3">
                <label for="department_id" class="form-label small mb-1">Bidang/UPT</label>
                <select name="department_id" id="department_id" class="form-select">
                    <option value="">Semua Bidang/UPT</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected((int) ($filters['department_id'] ?? 0) === $department->id)>
                            {{ $department->name }} — {{ $department->location->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endunless
            <div class="col-6 col-lg-3">
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
            <div class="col-12 col-lg-3 d-flex gap-2">
                <button type="submit" class="btn btn-gray-800 flex-grow-1">Terapkan</button>
                <a href="{{ route('admin.pic-audit.index') }}" class="btn btn-gray-200">Reset</a>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="small text-gray-600">
            {{ $logs->total() }} aksi PIC
            @if ($isPic)
                · hanya Bidang/UPT {{ auth()->user()->department?->name }}
            @endif
        </span>
        <a href="{{ route('admin.pic-audit.export', $filters) }}" class="btn btn-primary btn-sm">Ekspor PDF</a>
    </div>

    <div class="card card-body border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-cards pic-audit-table">
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th class="border-gray-200">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php([$number, $time, $pic, $guest, $organization, $purpose, $result] = $row['values'])
                        <tr>
                            <td data-label="No">{{ $number }}</td>
                            <td data-label="Waktu" class="text-nowrap">{{ $time }}</td>
                            <td data-label="PIC">{{ $pic }}</td>
                            <td data-label="Tamu" class="fw-bold">
                                <a href="{{ route('admin.visits.show', $row['visit_id']) }}">{{ $guest }}</a>
                            </td>
                            <td data-label="Instansi">{{ $organization }}</td>
                            <td data-label="Rincian keperluan" class="small text-break text-pre-line pic-audit-purpose">{{ $purpose }}</td>
                            <td data-label="Hasil" class="small text-break text-pre-line pic-audit-result">{{ $result }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) }}" class="text-center text-gray-600 py-4">Belum ada aksi PIC yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $logs->links() }}</div>
    </div>
@endsection

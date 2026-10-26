@extends('layouts.staff')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        @include('admin.dashboard.partials.stat-card', ['label' => 'Belum Ditugaskan', 'value' => $counts['unassigned'], 'color' => 'warning'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Menunggu PIC', 'value' => $counts['waiting_pic'], 'color' => 'info'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Sedang Direview', 'value' => $counts['processing'], 'color' => 'primary'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Selesai Hari Ini', 'value' => $counts['completed_today'], 'color' => 'success'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Total Kunjungan Hari Ini', 'value' => $counts['today']])
    </div>

    <div class="card card-body border-0 shadow table-wrapper table-responsive" data-dashboard-active data-cancel-url="{{ url('/admin/kunjungan/__ID__/batal') }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Tamu Aktif</h2>
            <a href="{{ route('admin.visits.active') }}" class="small">Lihat semua</a>
        </div>
        <table class="table table-hover align-middle mb-0 table-cards">
            <thead>
                <tr>
                    <th class="border-gray-200">No.</th>
                    <th class="border-gray-200">Nama</th>
                    <th class="border-gray-200">Keperluan</th>
                    <th class="border-gray-200">Departemen</th>
                    <th class="border-gray-200">Lokasi</th>
                    <th class="border-gray-200">Jam datang</th>
                    <th class="border-gray-200">Status</th>
                    <th class="border-gray-200">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($activeVisits as $visit)
                    <tr>
                        <td data-label="No.">No. {{ $visit->daily_number }}</td>
                        <td data-label="Nama" class="fw-bold">{{ $visit->name }}</td>
                        <td data-label="Keperluan">{{ $visit->purpose_category_name }}</td>
                        <td data-label="Departemen">{{ $visit->assignedDepartment?->name ?? '–' }}</td>
                        <td data-label="Lokasi">{{ $visit->location->name }}</td>
                        <td data-label="Jam datang">{{ $visit->check_in_at->isToday() ? $visit->check_in_at->format('H:i') : $visit->check_in_at->format('d/m H:i') }}</td>
                        <td data-label="Status"><span class="badge bg-{{ $visit->display_status['badge'] }}">{{ $visit->display_status['label'] }}</span></td>
                        <td data-label="Aksi">
                            <div class="d-flex flex-wrap gap-1 justify-content-end justify-content-md-start">
                                <button type="button" class="btn btn-sm btn-outline-danger" data-cancel-visit="{{ $visit->id }}"
                                        data-guest="No. {{ $visit->daily_number }} · {{ $visit->name }}" data-no-confirm>Batal</button>
                                <a href="{{ route('admin.visits.show', $visit->id) }}" class="btn btn-sm btn-outline-gray-800">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-600 py-4">Tidak ada tamu aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.visits.partials.cancel-modal', ['cancelReasons' => $cancelReasons])
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
@endpush

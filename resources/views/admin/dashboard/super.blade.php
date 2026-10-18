@extends('layouts.staff')

@section('title', 'Dashboard')

@section('content')
    <div class="row">
        @include('admin.dashboard.partials.stat-card', ['label' => 'Total Kunjungan', 'value' => $overview['total']])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Lokasi Aktif', 'value' => $overview['active_locations'], 'color' => 'primary'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Kunjungan Hari Ini', 'value' => $overview['today'], 'color' => 'success'])
        @include('admin.dashboard.partials.stat-card', ['label' => 'Tamu Menunggu', 'value' => $overview['waiting'], 'color' => 'warning'])
    </div>

    <div class="row">
        <div class="col-12 col-xl-7 mb-4">
            <div class="card border-0 shadow h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Kunjungan 14 hari terakhir</h2>
                    <div class="dashboard-chart">
                        <canvas data-visits-chart
                                data-labels="{{ json_encode($chart['labels']) }}"
                                data-values="{{ json_encode($chart['values']) }}"
                                aria-label="Grafik batang jumlah kunjungan per hari selama 14 hari terakhir" role="img"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5 mb-4">
            <div class="card card-body border-0 shadow h-100 table-wrapper table-responsive">
                <h2 class="h6 mb-3">10 kunjungan terbaru</h2>
                <table class="table table-sm align-middle mb-0 table-cards small">
                    <thead>
                        <tr>
                            <th class="border-gray-200">Waktu</th>
                            <th class="border-gray-200">Nama</th>
                            <th class="border-gray-200">Lokasi</th>
                            <th class="border-gray-200">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestVisits as $visit)
                            <tr>
                                <td data-label="Waktu">{{ $visit->check_in_at->format('d/m H:i') }}</td>
                                <td data-label="Nama"><a href="{{ route('admin.visits.show', $visit->id) }}">{{ $visit->name }}</a></td>
                                <td data-label="Lokasi">{{ $visit->location->name }}</td>
                                <td data-label="Status"><span class="badge bg-{{ $visit->display_status['badge'] }}">{{ $visit->display_status['label'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-gray-600 py-3">Belum ada kunjungan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-body border-0 shadow table-wrapper table-responsive">
        <h2 class="h6 mb-3">Ringkasan per lokasi</h2>
        <table class="table table-hover align-middle mb-0 table-cards">
            <thead>
                <tr>
                    <th class="border-gray-200">Lokasi</th>
                    <th class="border-gray-200">Belum ditugaskan</th>
                    <th class="border-gray-200">Menunggu PIC</th>
                    <th class="border-gray-200">Sedang direview</th>
                    <th class="border-gray-200">Selesai hari ini</th>
                    <th class="border-gray-200">Total hari ini</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($perLocation as $row)
                    <tr>
                        <td data-label="Lokasi" class="fw-bold">
                            {{ $row['location']->name }}@unless ($row['location']->is_active) <span class="badge bg-secondary">nonaktif</span>@endunless
                        </td>
                        <td data-label="Belum ditugaskan">{{ $row['counts']['unassigned'] }}</td>
                        <td data-label="Menunggu PIC">{{ $row['counts']['waiting_pic'] }}</td>
                        <td data-label="Sedang direview">{{ $row['counts']['processing'] }}</td>
                        <td data-label="Selesai hari ini">{{ $row['counts']['completed_today'] }}</td>
                        <td data-label="Total hari ini">{{ $row['counts']['today'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-600 py-4">Belum ada lokasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('assets/js/dashboard.js') }}"></script>
@endpush

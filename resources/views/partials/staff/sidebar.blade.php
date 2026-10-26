{{-- Sidebar panel staf dengan menu sesuai peran (PRD Bab 13.2). --}}
@php
    use App\Enums\UserRole;

    $role = auth()->user()->role;

    $menu = [
        ['label' => 'Dashboard', 'path' => 'admin', 'icon' => 'chart-pie', 'roles' => [UserRole::SuperAdmin, UserRole::Admin]],
        ['label' => 'Tamu Aktif', 'path' => 'admin/tamu', 'icon' => 'user-group', 'roles' => [UserRole::SuperAdmin, UserRole::Admin, UserRole::Receptionist]],
        ['label' => 'Penugasan', 'path' => 'admin/penugasan', 'icon' => 'clipboard-check', 'roles' => [UserRole::Pic]],
        ['label' => 'History', 'path' => 'admin/history', 'icon' => 'clock', 'roles' => [UserRole::SuperAdmin, UserRole::Admin]],
        ['label' => 'Lokasi', 'path' => 'admin/lokasi', 'icon' => 'location', 'roles' => [UserRole::SuperAdmin, UserRole::Admin]],
        ['label' => 'QR Code', 'path' => 'admin/qr', 'icon' => 'qrcode', 'roles' => [UserRole::SuperAdmin]],
        ['label' => 'Pertanyaan', 'path' => 'admin/kategori-keperluan', 'icon' => 'tag', 'roles' => [UserRole::SuperAdmin, UserRole::Admin]],
        ['label' => 'Petugas', 'path' => 'admin/petugas', 'icon' => 'identification', 'roles' => [UserRole::SuperAdmin, UserRole::Admin]],
        ['label' => 'Pengguna', 'path' => 'admin/pengguna', 'icon' => 'users', 'roles' => [UserRole::SuperAdmin]],
        ['label' => 'Audit Log', 'path' => 'admin/audit-log', 'icon' => 'document-text', 'roles' => [UserRole::SuperAdmin]],
        ['label' => 'Ganti Password', 'path' => 'admin/password', 'icon' => 'key', 'roles' => UserRole::cases()],
    ];
@endphp

<nav id="sidebarMenu" class="sidebar sitamu-sidebar d-lg-block text-white collapse">
    <div class="sidebar-inner px-4 pt-3">
        <div class="d-flex d-lg-none align-items-center justify-content-between pb-4">
            <div>
                <div class="fw-bold">{{ auth()->user()->name }}</div>
                <small class="text-gray-400">{{ $role->label() }}</small>
            </div>
            <a href="#sidebarMenu" class="text-white" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
               aria-controls="sidebarMenu" aria-expanded="true" aria-label="Tutup menu">
                <x-icon name="x" />
            </a>
        </div>

        <ul class="nav flex-column pt-3 pt-md-0">
            <li class="nav-item">
                <a href="{{ url('/admin') }}" class="nav-link d-flex align-items-center">
                    <img src="{{ asset('assets/img/logo-jatim.png') }}" class="sidebar-brand-logo me-2" alt="Logo Provinsi Jawa Timur">
                    <span class="sidebar-brand-wrap">
                        <span class="sidebar-brand-name">{{ config('app.name') }}</span>
                        <span class="sidebar-brand-sub">
                            <span class="d-block text-nowrap">Dinas Perindustrian dan Perdagangan</span>
                            <span class="d-block text-nowrap">Provinsi Jawa Timur</span>
                        </span>
                    </span>
                </a>
            </li>
            <li role="separator" class="dropdown-divider mt-3 mb-3 border-gray-700"></li>

            @foreach ($menu as $item)
                @continue(! in_array($role, $item['roles'], true))
                @php
                    // Semua halaman staf berawalan admin/, jadi Dashboard hanya aktif di /admin itu sendiri.
                    $isActive = request()->is($item['path'])
                        || ($item['path'] !== 'admin' && request()->is($item['path'].'/*'));
                @endphp
                <li class="nav-item {{ $isActive ? 'active' : '' }}">
                    <a href="{{ url($item['path']) }}" class="nav-link">
                        <span class="sidebar-icon"><x-icon :name="$item['icon']" class="icon icon-xs me-2" /></span>
                        <span class="sidebar-text">{{ $item['label'] }}</span>
                        @if ($item['path'] === 'admin/penugasan')
                            <span class="badge bg-danger ms-1 menu-badge" data-assignment-badge @if ($newAssignmentCount === 0) hidden @endif>{{ $newAssignmentCount }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</nav>

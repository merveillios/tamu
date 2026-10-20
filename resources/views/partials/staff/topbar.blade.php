{{-- Navbar atas panel staf: judul halaman, nama akun, peran, dan tombol keluar. --}}
<nav class="navbar navbar-top navbar-expand navbar-dashboard navbar-dark ps-0 pe-2 pb-0">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h1 class="h5 mb-0 text-gray-800">@yield('title')</h1>

            <ul class="navbar-nav align-items-center">
                <li class="nav-item dropdown ms-lg-3">
                    <a class="nav-link dropdown-toggle pt-1 px-0" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="media d-flex align-items-center">
                            <div class="media-body me-2 text-dark text-end d-none d-lg-block">
                                <span class="mb-0 font-small fw-bold text-gray-900 d-block">{{ auth()->user()->name }}</span>
                                <span class="small text-gray-500">{{ auth()->user()->role->label() }}</span>
                            </div>
                            <span class="avatar-initial">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        </div>
                    </a>
                    <div class="dropdown-menu dashboard-dropdown dropdown-menu-end mt-2 py-1">
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('admin.password.edit') }}">
                            <x-icon name="key" class="dropdown-icon text-gray-400 me-2" />
                            Ganti Password
                        </a>
                        <div role="separator" class="dropdown-divider my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex align-items-center">
                                <x-icon name="logout" class="dropdown-icon text-danger me-2" />
                                Keluar
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>

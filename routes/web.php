<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

/*
| Seluruh route SiTamu (PRD Bab 10). Tidak ada routes/api.php.
*/

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware(['auth', 'active'])->name('logout');

Route::middleware(['auth', 'auth.session', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/pengguna/tambah', [UserController::class, 'store'])->name('users.store');
        Route::get('/pengguna/{id}/ubah', [UserController::class, 'edit'])->whereNumber('id')->name('users.edit');
        Route::post('/pengguna/{id}/ubah', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
        Route::post('/pengguna/{id}/status', [UserController::class, 'toggleStatus'])->whereNumber('id')->name('users.status');
        Route::post('/pengguna/{id}/buka-kunci', [UserController::class, 'unlock'])->whereNumber('id')->name('users.unlock');

        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/lokasi/tambah', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/lokasi/tambah', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/lokasi/{id}/ubah', [LocationController::class, 'edit'])->whereNumber('id')->name('locations.edit');
        Route::post('/lokasi/{id}/ubah', [LocationController::class, 'update'])->whereNumber('id')->name('locations.update');
        Route::post('/lokasi/{id}/status', [LocationController::class, 'toggleStatus'])->whereNumber('id')->name('locations.status');
        Route::post('/lokasi/{id}/hapus', [LocationController::class, 'destroy'])->whereNumber('id')->name('locations.destroy');
    });
});

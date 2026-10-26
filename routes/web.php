<?php

use App\Http\Controllers\Admin\ActiveVisitController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PurposeCategoryController;
use App\Http\Controllers\Admin\QrController;
use App\Http\Controllers\Admin\StaffMemberController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VisitController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Guest\GoogleVerificationController;
use App\Http\Controllers\Guest\GuestEntryController;
use App\Http\Controllers\Guest\GuestFormController;
use App\Http\Controllers\Guest\GuestHistoryController;
use Illuminate\Support\Facades\Route;

/*
| Seluruh route SiTamu (PRD Bab 10). Tidak ada routes/api.php.
*/

Route::view('/', 'home')->name('home');

Route::prefix('tamu/{token}')->name('guest.')->group(function () {
    Route::get('/', [GuestEntryController::class, 'show'])->name('show');
    Route::get('/bantuan', [GuestEntryController::class, 'help'])->name('help');
    Route::post('/verifikasi-google', [GoogleVerificationController::class, 'verify'])->middleware('throttle:google-verify')->name('verify');
    Route::post('/ganti-akun', [GoogleVerificationController::class, 'switchAccount'])->name('switch');
    Route::get('/riwayat', [GuestHistoryController::class, 'show'])->name('history');
    Route::get('/formulir', [GuestFormController::class, 'show'])->name('form');
    Route::post('/formulir', [GuestFormController::class, 'store'])->middleware('throttle:guest-form')->name('store');
    Route::get('/terima-kasih', [GuestFormController::class, 'thanks'])->name('thanks');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware(['auth', 'active'])->name('logout');

Route::middleware(['auth', 'auth.session', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::post('/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/kunjungan/{id}', [VisitController::class, 'show'])->whereNumber('id')->name('visits.show');

    Route::middleware('role:receptionist,admin,super_admin')->group(function () {
        Route::get('/tamu', [ActiveVisitController::class, 'index'])->name('visits.active');
        Route::get('/tamu/data', [ActiveVisitController::class, 'data'])->name('visits.active.data');
        Route::post('/kunjungan/{id}/batal', [VisitController::class, 'cancel'])->whereNumber('id')->name('visits.cancel');
        Route::get('/kunjungan/{id}/tanda-tangan', [VisitController::class, 'signature'])->whereNumber('id')->name('visits.signature');
    });

    Route::middleware('role:receptionist')->group(function () {
        Route::get('/kunjungan/{id}/departemen', [VisitController::class, 'departments'])->whereNumber('id')->name('visits.departments');
        Route::post('/kunjungan/{id}/tugaskan', [VisitController::class, 'assign'])->whereNumber('id')->name('visits.assign');
    });

    Route::middleware('role:pic')->group(function () {
        Route::get('/penugasan', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/penugasan/data', [AssignmentController::class, 'data'])->name('assignments.data');
        Route::post('/kunjungan/{id}/terima', [AssignmentController::class, 'accept'])->whereNumber('id')->name('visits.accept');
        Route::post('/kunjungan/{id}/selesai', [AssignmentController::class, 'complete'])->whereNumber('id')->name('visits.complete');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/kunjungan/{id}/buka-kembali', [VisitController::class, 'reopen'])->whereNumber('id')->name('visits.reopen');
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    });

    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/lokasi', [LocationController::class, 'index'])->name('locations.index');
        Route::get('/lokasi/tambah', [LocationController::class, 'create'])->name('locations.create');
        Route::post('/lokasi/tambah', [LocationController::class, 'store'])->name('locations.store');
        Route::get('/lokasi/{id}/ubah', [LocationController::class, 'edit'])->whereNumber('id')->name('locations.edit');
        Route::post('/lokasi/{id}/ubah', [LocationController::class, 'update'])->whereNumber('id')->name('locations.update');
        Route::post('/lokasi/{id}/status', [LocationController::class, 'toggleStatus'])->whereNumber('id')->name('locations.status');
        Route::post('/lokasi/{id}/hapus', [LocationController::class, 'destroy'])->whereNumber('id')->name('locations.destroy');

        Route::get('/lokasi/{id}/departemen', [DepartmentController::class, 'index'])->whereNumber('id')->name('departments.index');
        Route::post('/lokasi/{id}/departemen', [DepartmentController::class, 'store'])->whereNumber('id')->name('departments.store');
        Route::post('/departemen/{id}/ubah', [DepartmentController::class, 'update'])->whereNumber('id')->name('departments.update');
        Route::post('/departemen/{id}/status', [DepartmentController::class, 'toggleStatus'])->whereNumber('id')->name('departments.status');

        Route::get('/qr', [QrController::class, 'index'])->name('qr.index');
        Route::post('/qr', [QrController::class, 'store'])->name('qr.store');
        Route::post('/qr/{id}/departemen', [QrController::class, 'changeDepartment'])->whereNumber('id')->name('qr.department');
        Route::post('/qr/{id}/resepsionis', [QrController::class, 'changeReceptionist'])->whereNumber('id')->name('qr.receptionist');
        Route::post('/qr/{id}/status', [QrController::class, 'toggleStatus'])->whereNumber('id')->name('qr.status');
        Route::post('/qr/{id}/regenerate', [QrController::class, 'regenerate'])->whereNumber('id')->name('qr.regenerate');
        Route::get('/qr/{id}/unduh', [QrController::class, 'download'])->whereNumber('id')->name('qr.download');
        Route::get('/qr/{id}/cetak', [QrController::class, 'print'])->whereNumber('id')->name('qr.print');

        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::get('/pengguna/tambah', [UserController::class, 'create'])->name('users.create');
        Route::post('/pengguna/tambah', [UserController::class, 'store'])->name('users.store');
        Route::get('/pengguna/{id}/ubah', [UserController::class, 'edit'])->whereNumber('id')->name('users.edit');
        Route::post('/pengguna/{id}/ubah', [UserController::class, 'update'])->whereNumber('id')->name('users.update');
        Route::post('/pengguna/{id}/status', [UserController::class, 'toggleStatus'])->whereNumber('id')->name('users.status');
        Route::post('/pengguna/{id}/buka-kunci', [UserController::class, 'unlock'])->whereNumber('id')->name('users.unlock');

        Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
        Route::get('/history/export', [HistoryController::class, 'export'])->name('history.export');

        Route::get('/kategori-keperluan', [PurposeCategoryController::class, 'index'])->name('categories.index');
        Route::get('/kategori-keperluan/tambah', [PurposeCategoryController::class, 'create'])->name('categories.create');
        Route::post('/kategori-keperluan/tambah', [PurposeCategoryController::class, 'store'])->name('categories.store');
        Route::get('/kategori-keperluan/{id}/ubah', [PurposeCategoryController::class, 'edit'])->whereNumber('id')->name('categories.edit');
        Route::post('/kategori-keperluan/{id}/ubah', [PurposeCategoryController::class, 'update'])->whereNumber('id')->name('categories.update');
        Route::post('/kategori-keperluan/{id}/status', [PurposeCategoryController::class, 'toggleStatus'])->whereNumber('id')->name('categories.status');

        Route::get('/petugas', [StaffMemberController::class, 'index'])->name('staff.index');
        Route::get('/petugas/tambah', [StaffMemberController::class, 'create'])->name('staff.create');
        Route::post('/petugas/tambah', [StaffMemberController::class, 'store'])->name('staff.store');
        Route::get('/petugas/{id}/ubah', [StaffMemberController::class, 'edit'])->whereNumber('id')->name('staff.edit');
        Route::post('/petugas/{id}/ubah', [StaffMemberController::class, 'update'])->whereNumber('id')->name('staff.update');
        Route::post('/petugas/{id}/status', [StaffMemberController::class, 'toggleStatus'])->whereNumber('id')->name('staff.status');
    });
});

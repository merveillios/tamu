<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Ganti password sendiri untuk semua peran staf (USR-3).
 */
class PasswordController extends Controller
{
    /**
     * Menampilkan form ganti password.
     */
    public function edit(): View
    {
        return view('admin.password.edit');
    }

    /**
     * Menyimpan password baru lalu mengakhiri semua sesi akun, termasuk sesi ini.
     * Sesi di perangkat lain ditolak oleh middleware auth.session karena hash password berubah.
     */
    public function update(ChangePasswordRequest $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password')])->save();

        $audit->record('auth.password_changed', $user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Password berhasil diganti. Silakan login kembali.');
    }
}

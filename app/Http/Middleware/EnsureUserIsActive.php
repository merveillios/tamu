<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akun staf yang sudah dinonaktifkan pada request berikutnya (USR-1).
 */
class EnsureUserIsActive
{
    /**
     * Mengeluarkan akun nonaktif dan mengarahkannya ke halaman login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            abort(401);
        }

        return redirect()->route('login')->with('error', 'Akun Anda tidak aktif.');
    }
}

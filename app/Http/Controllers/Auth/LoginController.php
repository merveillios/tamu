<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman login staf. Proses login dan penguncian akun dibangun di M1 (AUTH-1).
 */
class LoginController extends Controller
{
    /**
     * Menampilkan form login.
     */
    public function show(): View
    {
        return view('auth.login');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Services\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login dan logout staf (AUTH-1, AUTH-3).
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

    /**
     * Memproses login; pesan gagal selalu seragam.
     */
    public function store(LoginRequest $request, LoginService $login): RedirectResponse
    {
        $user = $login->attempt($request->validated('email'), $request->validated('password'));

        if ($user === null) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}

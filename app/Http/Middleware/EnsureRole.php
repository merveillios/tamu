<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi route untuk satu atau beberapa peran, misalnya role:admin,super_admin.
 *
 * Ini lapis pertama pembatasan akses; cakupan data tetap dicek AccessScope (Bab 12.2).
 */
class EnsureRole
{
    /**
     * Menolak dengan HTTP 403 bila peran akun tidak termasuk daftar.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if (! $request->user()?->hasRole(...$allowed)) {
            abort(403);
        }

        return $next($request);
    }
}

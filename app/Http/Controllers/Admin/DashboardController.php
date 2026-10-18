<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\AccessScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman awal panel staf. Isi dashboard per peran dibangun di M6 (DSH-1 sampai DSH-5).
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan halaman awal staf.
     */
    public function index(Request $request, AccessScope $scope): View
    {
        $user = $request->user();

        return view('admin.home', [
            // USR-2: Admin tanpa lokasi tetap bisa login tetapi diberi tahu.
            'adminWithoutLocation' => $user->role === UserRole::Admin && $scope->locationIds($user) === [],
        ]);
    }
}

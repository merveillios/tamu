<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Halaman awal panel staf. Isi dashboard per peran dibangun di M6 (DSH-1 sampai DSH-5).
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan halaman awal staf.
     */
    public function index(): View
    {
        return view('admin.home');
    }
}

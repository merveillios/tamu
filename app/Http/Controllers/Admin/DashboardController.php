<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\VisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Dashboard per peran (DSH-1 sampai DSH-5).
 */
class DashboardController extends Controller
{
    /**
     * Admin dan Super Admin melihat dashboard; Resepsionis dan PIC langsung ke halaman kerjanya (DSH-5).
     */
    public function index(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        return match ($user->role) {
            UserRole::Receptionist => redirect()->route('admin.visits.active'),
            UserRole::Pic => redirect()->route('admin.assignments.index'),
            UserRole::Admin => view('admin.dashboard.admin', [
                'counts' => $dashboard->counts($user),
                'activeVisits' => $dashboard->activePreview($user),
                'cancelReasons' => VisitService::CANCEL_REASONS,
            ]),
            UserRole::SuperAdmin => view('admin.dashboard.super', [
                'overview' => $dashboard->overview($user),
                'perLocation' => $dashboard->perLocation($user),
                'chart' => $dashboard->dailyVisits($user),
                'latestVisits' => $dashboard->latestVisits($user),
            ]),
        };
    }
}

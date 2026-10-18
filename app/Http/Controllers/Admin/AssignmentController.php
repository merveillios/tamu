<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VisitStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteVisitRequest;
use App\Services\VisitListService;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar penugasan departemen untuk akun PIC (PIC-1 sampai PIC-4).
 */
class AssignmentController extends Controller
{
    public function __construct(
        private readonly VisitListService $lists,
        private readonly VisitService $visits,
    ) {
    }

    /**
     * Halaman Penugasan; baris dimuat dan diperbarui lewat polling JSON.
     */
    public function index(Request $request): View
    {
        return view('admin.assignments.index', [
            'picWithoutDepartment' => $request->user()->department_id === null,
        ]);
    }

    /**
     * Data JSON untuk polling: penugasan baru, sedang direview, jumlah baru, dan waktu server.
     */
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $rows = $this->lists->picAssignments($user)->map(fn ($visit) => $this->lists->row($visit, $user));

        return response()->json([
            'ok' => true,
            'data' => [
                'new' => $rows->where('status', VisitStatus::Waiting->value)->values(),
                'reviewing' => $rows->where('status', VisitStatus::Processing->value)->values(),
                'new_count' => $rows->where('status', VisitStatus::Waiting->value)->count(),
                'server_time' => now()->format('H:i:s'),
            ],
        ]);
    }

    /**
     * Menerima penugasan (PIC-2).
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        $visit = $this->visits->findForPic($id, $request->user());
        $this->visits->accept($visit, $request->user());

        return response()->json(['ok' => true, 'data' => ['message' => "Tamu No. {$visit->daily_number} diterima."]]);
    }

    /**
     * Menulis hasil dan menyelesaikan kunjungan (PIC-3).
     */
    public function complete(CompleteVisitRequest $request, int $id): JsonResponse
    {
        $visit = $this->visits->findForPic($id, $request->user());
        $this->visits->complete($visit, $request->validated('result'), $request->user());

        return response()->json(['ok' => true, 'data' => ['message' => "Kunjungan No. {$visit->daily_number} selesai."]]);
    }
}

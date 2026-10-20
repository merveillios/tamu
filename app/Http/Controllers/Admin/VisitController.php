<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\VisitActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignVisitRequest;
use App\Http\Requests\CancelVisitRequest;
use App\Http\Requests\ReopenVisitRequest;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\VisitSignature;
use App\Services\AccessScope;
use App\Services\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Detail kunjungan dan aksi per kunjungan: tugaskan, batal, buka kembali, tanda tangan.
 */
class VisitController extends Controller
{
    public function __construct(
        private readonly AccessScope $scope,
        private readonly VisitService $visits,
    ) {
    }

    /**
     * Detail kunjungan sesuai peran (ADM-4, PIC-5, Bab 13.2).
     */
    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $user)->load([
            'location:id,name', 'qrCode:id,label', 'suggestedDepartment:id,name', 'assignedDepartment:id,name',
            'servedBy:id,name', 'processedBy:id,name', 'closedBy:id,name',
            'assignments' => fn ($query) => $query->with(['department:id,name', 'staffMember:id,name', 'assignedBy:id,name'])->orderBy('id'),
            'statusLogs' => fn ($query) => $query->with(['changedBy:id,name', 'staffMember:id,name'])->orderBy('id'),
        ]);

        $canSeeSignature = $user->hasRole(UserRole::Admin, UserRole::SuperAdmin, UserRole::Receptionist);

        return view('admin.visits.show', [
            'visit' => $visit,
            // Gambar tidak di-JOIN; keberadaannya dicek dengan query terpisah (Bab 9.2).
            'hasSignature' => $canSeeSignature && VisitSignature::query()->whereKey($visit->id)->exists(),
            'canSeeSignature' => $canSeeSignature,
            'canReopen' => $user->role === UserRole::Admin && ! $visit->status->isActive(),
        ]);
    }

    /**
     * Pilihan departemen dan petugas untuk modal Tugaskan (Bab 10.4).
     */
    public function departments(Request $request, int $id): JsonResponse
    {
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $request->user());

        if ($visit->status !== VisitStatus::Waiting) {
            throw VisitActionException::conflict('Kunjungan ini sudah diterima PIC. Daftar akan dimuat ulang.');
        }

        $departments = $this->visits->assignableDepartments($visit);
        $suggested = $departments->contains('id', $visit->suggested_department_id) ? $visit->suggested_department_id : null;

        return response()->json([
            'ok' => true,
            'data' => [
                'departments' => $departments->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'staff' => $department->staffMembers->map(fn ($staff) => ['id' => $staff->id, 'name' => $staff->name])->values(),
                ])->values(),
                'selected_department_id' => $visit->assigned_department_id ?? $suggested,
                'selected_staff_member_id' => $visit->served_by_staff_id,
                'suggested_department_id' => $suggested,
            ],
        ]);
    }

    /**
     * Menugaskan atau menugaskan ulang kunjungan (RSP-2, RSP-3).
     */
    public function assign(AssignVisitRequest $request, int $id): JsonResponse
    {
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $request->user());
        $this->visits->assign($visit, (int) $request->validated('department_id'), (int) $request->validated('staff_member_id'), $request->user());

        return response()->json(['ok' => true, 'data' => ['message' => "Tamu No. {$visit->daily_number} ditugaskan."]]);
    }

    /**
     * Membatalkan kunjungan (RSP-4, ADM-2).
     */
    public function cancel(CancelVisitRequest $request, int $id): JsonResponse
    {
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $request->user());
        $this->visits->cancel($visit, $request->validated('reason'), $request->validated('reason_text'), $request->user());

        return response()->json(['ok' => true, 'data' => ['message' => "Kunjungan No. {$visit->daily_number} dibatalkan."]]);
    }

    /**
     * Membuka kembali kunjungan Selesai atau Dibatalkan (ADM-3).
     */
    public function reopen(ReopenVisitRequest $request, int $id): JsonResponse
    {
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $request->user());
        $this->visits->reopen($visit, $request->validated('note'), $request->user());

        return response()->json(['ok' => true, 'data' => ['message' => 'Kunjungan dibuka kembali.']]);
    }

    /**
     * Gambar tanda tangan untuk Admin dan Super Admin, tidak pernah disajikan dari public/ (TAM-9).
     */
    public function signature(Request $request, int $id): Response
    {
        $visit = $this->scope->findOrDeny(GuestVisit::class, $id, $request->user());
        $signature = VisitSignature::query()->whereKey($visit->id)->first() ?? abort(404);

        return response($signature->image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

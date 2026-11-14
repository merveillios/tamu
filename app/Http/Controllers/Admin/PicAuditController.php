<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StaffMember;
use App\Services\HistoryService;
use App\Services\PicAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman Audit Log PIC dan ekspor PDF-nya (AUD-4 sampai AUD-7). Admin dan Super Admin melihat
 * semua Bidang/UPT; akun PIC hanya Bidang/UPT akunnya, dipaksa di server lewat filter.
 */
class PicAuditController extends Controller
{
    public function __construct(private readonly PicAuditService $audits)
    {
    }

    /**
     * Daftar aksi PIC dengan filter, 25 baris per halaman.
     */
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $logs = $this->audits->query($filters)->paginate(25)->withQueryString();
        $ownDepartmentId = $this->ownDepartmentId($request);

        return view('admin.pic-audit.index', [
            'logs' => $logs,
            'rows' => $this->audits->rows($logs->getCollection(), $logs->firstItem() ?? 1),
            'columns' => PicAuditService::COLUMNS,
            'filters' => $filters,
            'periods' => HistoryService::PERIODS,
            // Orang yang ditemui (daftar Petugas), dikelompokkan per Bidang/UPT.
            'staffMembers' => StaffMember::query()
                ->when($ownDepartmentId !== null, fn ($query) => $query->where('department_id', $ownDepartmentId))
                ->with(['department:id,name,location_id', 'department.location:id,name'])
                ->orderBy('name')
                ->get(['id', 'name', 'department_id', 'is_active'])
                ->groupBy(fn (StaffMember $staff) => $staff->department->name.' — '.$staff->department->location->name)
                ->sortKeys(),
            // PIC tidak memilih Bidang/UPT karena hanya melihat Bidang/UPT akunnya.
            'departments' => $ownDepartmentId !== null ? collect() : Department::query()->with('location:id,name')->orderBy('name')->get(['id', 'name', 'location_id']),
            'isPic' => $ownDepartmentId !== null,
        ]);
    }

    /**
     * Unduh PDF dengan filter yang sama.
     */
    public function export(Request $request): Response
    {
        return $this->audits->download($this->filters($request), $request->user());
    }

    /**
     * Filter yang valid dari query string, tanpa nilai kosong.
     *
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $ownDepartmentId = $this->ownDepartmentId($request);

        $validated = $request->validate([
            'staff_member_id' => [
                'nullable', 'integer',
                Rule::exists('staff_members', 'id')->when($ownDepartmentId !== null, fn ($rule) => $rule->where('department_id', $ownDepartmentId)),
            ],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'period' => ['nullable', Rule::in(array_keys(HistoryService::PERIODS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ], [
            'staff_member_id.*' => 'PIC tidak ditemukan.',
            'department_id.*' => 'Bidang/UPT tidak ditemukan.',
            'from.*' => 'Tanggal awal tidak valid.',
            'to.*' => 'Tanggal akhir tidak valid atau sebelum tanggal awal.',
        ]);

        $filters = array_filter(
            collect($validated)->except('page')->all(),
            fn ($value) => $value !== null && $value !== '',
        );

        if ($ownDepartmentId !== null) {
            // Input department_id dari browser diabaikan untuk PIC.
            $filters['department_id'] = $ownDepartmentId;
        }

        return $filters;
    }

    /**
     * Bidang/UPT akun PIC yang sedang login, atau null untuk Admin dan Super Admin.
     * Akun PIC tanpa Bidang/UPT diberi 0 sehingga tidak melihat data apa pun.
     */
    private function ownDepartmentId(Request $request): ?int
    {
        $user = $request->user();

        return $user->role === UserRole::Pic ? (int) ($user->department_id ?? 0) : null;
    }
}

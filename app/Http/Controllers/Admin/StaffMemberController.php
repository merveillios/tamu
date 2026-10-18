<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NameRequest;
use App\Models\Department;
use App\Models\StaffMember;
use App\Services\AccessScope;
use App\Services\StaffMemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar petugas per departemen untuk Admin dan Super Admin (PTG-1, PTG-2).
 */
class StaffMemberController extends Controller
{
    public function __construct(private readonly AccessScope $scope)
    {
    }

    /**
     * Daftar petugas departemen terpilih.
     */
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->forUser($request->user())
            ->with('location:id,name')
            ->get()
            ->sortBy(fn (Department $department) => $department->location->name.'|'.$department->name);

        $departmentId = $request->query('department_id', $departments->first()?->id);
        $department = $departmentId === null ? null : $this->scope->findOrDeny(Department::class, $departmentId, $request->user());

        $staffMembers = $department === null ? collect() : StaffMember::query()
            ->forUser($request->user())
            ->where('department_id', $department->id)
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', [
            'departments' => $departments->groupBy(fn (Department $item) => $item->location->name),
            'department' => $department,
            'staffMembers' => $staffMembers,
        ]);
    }

    /**
     * Form petugas baru untuk departemen terpilih.
     */
    public function create(Request $request): View
    {
        $department = $this->scope->findOrDeny(Department::class, (int) $request->query('department_id'), $request->user());

        return view('admin.staff.form', ['staffMember' => new StaffMember(), 'department' => $department]);
    }

    /**
     * Menyimpan petugas baru.
     */
    public function store(NameRequest $request, StaffMemberService $staff): RedirectResponse
    {
        $department = $this->scope->findOrDeny(Department::class, (int) $request->query('department_id'), $request->user());
        $member = $staff->create($department, $request->validated('name'), $request->user());

        return redirect()->route('admin.staff.index', ['department_id' => $department->id])
            ->with('success', "Petugas {$member->name} berhasil ditambahkan.");
    }

    /**
     * Form ubah petugas.
     */
    public function edit(Request $request, int $id): View
    {
        $member = $this->scope->findOrDeny(StaffMember::class, $id, $request->user());

        return view('admin.staff.form', ['staffMember' => $member, 'department' => $member->department]);
    }

    /**
     * Menyimpan perubahan nama petugas.
     */
    public function update(NameRequest $request, int $id, StaffMemberService $staff): RedirectResponse
    {
        $member = $this->scope->findOrDeny(StaffMember::class, $id, $request->user());
        $staff->rename($member, $request->validated('name'), $request->user());

        return redirect()->route('admin.staff.index', ['department_id' => $member->department_id])
            ->with('success', "Petugas {$member->name} berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan petugas.
     */
    public function toggleStatus(Request $request, int $id, StaffMemberService $staff): RedirectResponse
    {
        $member = $this->scope->findOrDeny(StaffMember::class, $id, $request->user());
        $staff->setActive($member, ! $member->is_active, $request->user());

        $message = $member->is_active ? "Petugas {$member->name} diaktifkan." : "Petugas {$member->name} dinonaktifkan.";

        return back()->with('success', $message);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\NameRequest;
use App\Models\Department;
use App\Models\Location;
use App\Services\AccessScope;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen departemen per lokasi oleh Super Admin (DEP-1 sampai DEP-3).
 */
class DepartmentController extends Controller
{
    public function __construct(private readonly AccessScope $scope)
    {
    }

    /**
     * Daftar departemen sebuah lokasi beserta akun PIC dan jumlah petugas aktif.
     */
    public function index(Request $request, int $locationId): View
    {
        $location = $this->scope->findOrDeny(Location::class, $locationId, $request->user());

        $departments = Department::query()
            ->forUser($request->user())
            ->where('location_id', $location->id)
            ->with(['picUsers' => fn ($query) => $query->where('role', UserRole::Pic)->where('is_active', true)])
            ->withCount(['staffMembers as active_staff_count' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', ['location' => $location, 'departments' => $departments]);
    }

    /**
     * Menambah departemen di lokasi.
     */
    public function store(NameRequest $request, int $locationId, DepartmentService $departments): RedirectResponse
    {
        $location = $this->scope->findOrDeny(Location::class, $locationId, $request->user());
        $department = $departments->create($location, $request->validated('name'), $request->user());

        return back()->with('success', "Departemen {$department->name} berhasil ditambahkan.");
    }

    /**
     * Mengubah nama departemen.
     */
    public function update(NameRequest $request, int $id, DepartmentService $departments): RedirectResponse
    {
        $department = $this->scope->findOrDeny(Department::class, $id, $request->user());
        $departments->rename($department, $request->validated('name'), $request->user());

        return back()->with('success', "Departemen {$department->name} berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan departemen (DEP-2).
     */
    public function toggleStatus(Request $request, int $id, DepartmentService $departments): RedirectResponse
    {
        $department = $this->scope->findOrDeny(Department::class, $id, $request->user());
        $departments->setActive($department, ! $department->is_active, $request->user());

        $message = $department->is_active ? "Departemen {$department->name} diaktifkan." : "Departemen {$department->name} dinonaktifkan.";

        return back()->with('success', $message);
    }
}

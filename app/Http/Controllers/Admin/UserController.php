<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen akun staf oleh Super Admin (USR-1 sampai USR-5, AUTH-4).
 */
class UserController extends Controller
{
    /**
     * Daftar akun dengan pencarian serta filter peran dan status.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'in:'.implode(',', array_column(UserRole::cases(), 'value'))],
            'status' => ['nullable', 'in:aktif,nonaktif,terkunci'],
        ]);

        $users = User::query()
            ->with(['locations:id,name', 'department.location:id,name'])
            ->withCount('qrCodes')
            ->when($filters['q'] ?? null, function ($query, string $search) {
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->where('role', $role))
            ->when($filters['status'] ?? null, fn ($query, string $status) => match ($status) {
                'aktif' => $query->where('is_active', true),
                'nonaktif' => $query->where('is_active', false),
                'terkunci' => $query->where('locked_until', '>', now()),
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Form akun baru.
     */
    public function create(): View
    {
        return view('admin.users.form', $this->formOptions(new User(['role' => UserRole::Receptionist])));
    }

    /**
     * Menyimpan akun baru.
     */
    public function store(StoreUserRequest $request, UserAccountService $accounts): RedirectResponse
    {
        $user = $accounts->create($request->validated(), $request->user());

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil dibuat.");
    }

    /**
     * Form ubah akun.
     */
    public function edit(int $id): View
    {
        return view('admin.users.form', $this->formOptions(User::with('locations')->findOrFail($id)));
    }

    /**
     * Menyimpan perubahan akun.
     */
    public function update(UpdateUserRequest $request, int $id, UserAccountService $accounts): RedirectResponse
    {
        $user = $accounts->update(User::findOrFail($id), $request->validated(), $request->user());

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Data pilihan untuk form akun: peran, departemen aktif per lokasi, dan lokasi.
     *
     * @return array<string, mixed>
     */
    private function formOptions(User $user): array
    {
        return [
            'user' => $user,
            'roles' => UserRole::cases(),
            'departments' => Department::query()
                ->with('location:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Department $department) => $department->location->name)
                ->sortKeys(),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ];
    }
}

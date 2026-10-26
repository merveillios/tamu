<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen akun staf oleh Admin dan Super Admin (USR-1, USR-3 sampai USR-6, AUTH-4).
 * Admin hanya mengelola akun Resepsionis dan PIC (USR-6).
 */
class UserController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

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
            ->with('department.location:id,name')
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
    public function create(Request $request): View
    {
        return view('admin.users.form', $this->formOptions(new User(['role' => UserRole::Receptionist]), $request->user()));
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
    public function edit(Request $request, int $id): View
    {
        return view('admin.users.form', $this->formOptions($this->findManageable($request, $id), $request->user()));
    }

    /**
     * Menyimpan perubahan akun.
     */
    public function update(UpdateUserRequest $request, int $id, UserAccountService $accounts): RedirectResponse
    {
        $user = $accounts->update($this->findManageable($request, $id), $request->validated(), $request->user());

        return redirect()->route('admin.users.index')->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Mengaktifkan atau menonaktifkan akun (USR-4, USR-5).
     */
    public function toggleStatus(Request $request, int $id, UserAccountService $accounts): RedirectResponse
    {
        $user = $this->findManageable($request, $id);
        $accounts->setActive($user, ! $user->is_active, $request->user());

        $message = $user->is_active ? "Akun {$user->name} diaktifkan." : "Akun {$user->name} dinonaktifkan.";

        return back()->with('success', $message);
    }

    /**
     * Membuka kunci akun yang terkunci karena gagal login (AUTH-4).
     */
    public function unlock(Request $request, int $id, UserAccountService $accounts): RedirectResponse
    {
        $user = $this->findManageable($request, $id);
        $accounts->unlock($user, $request->user());

        return back()->with('success', "Kunci akun {$user->name} telah dibuka.");
    }

    /**
     * Mencari akun yang boleh diubah pelaku. Akun di luar hak pelaku ditolak 403
     * dan dicatat sebagai access.denied (USR-6).
     */
    private function findManageable(Request $request, int $id): User
    {
        $user = User::findOrFail($id);

        if (! $request->user()->canManageAccount($user)) {
            $this->audit->record('access.denied', null, ['model' => 'User', 'id' => $user->id], $request->user());
            abort(403);
        }

        return $user;
    }

    /**
     * Data pilihan untuk form akun: peran yang boleh diberikan pelaku dan departemen aktif per lokasi.
     *
     * @return array<string, mixed>
     */
    private function formOptions(User $user, User $actor): array
    {
        return [
            'user' => $user,
            'roles' => $actor->role->manageableRoles(),
            'departments' => Department::query()
                ->with('location:id,name')
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->groupBy(fn (Department $department) => $department->location->name)
                ->sortKeys(),
        ];
    }
}

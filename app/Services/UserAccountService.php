<?php

namespace App\Services;

use App\Enums\QrStatus;
use App\Enums\UserRole;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis akun staf yang dikelola Super Admin (USR-1, USR-2, USR-4, USR-5, AUTH-4).
 */
class UserAccountService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Membuat akun staf baru beserta lokasi Admin bila ada.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $role = UserRole::from($data['role']);
            $departmentId = $role === UserRole::Pic ? (int) $data['department_id'] : null;

            $this->ensureSingleActivePic($role, $departmentId, null);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $role,
                'department_id' => $departmentId,
                'is_active' => true,
            ]);

            $this->audit->record('user.create', $user, ['role' => $role->value], $actor);
            $this->syncLocations($user, $role === UserRole::Admin ? $data['location_ids'] : [], $actor);

            return $user;
        });
    }

    /**
     * Mengubah data akun; password hanya diganti bila diisi.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $role = UserRole::from($data['role']);
            $departmentId = $role === UserRole::Pic ? (int) $data['department_id'] : null;

            $this->ensureNotSelfDowngrade($user, $actor, $role, $user->is_active);
            $this->ensureSuperAdminRemains($user, $role, $user->is_active);
            $this->ensureReceptionistReleased($user, $role, $user->is_active);
            if ($user->is_active) {
                $this->ensureSingleActivePic($role, $departmentId, $user->id);
            }

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role,
                'department_id' => $departmentId,
            ]);
            $changedFields = array_keys($user->getDirty());

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();

            if ($changedFields !== []) {
                $this->audit->record('user.update', $user, ['fields' => $changedFields], $actor);
            }

            if (! empty($data['password'])) {
                $this->audit->record('user.password_reset', $user, [], $actor);
            }

            $this->syncLocations($user, $role === UserRole::Admin ? $data['location_ids'] : [], $actor);

            return $user;
        });
    }

    /**
     * Mengaktifkan atau menonaktifkan akun.
     */
    public function setActive(User $user, bool $active, User $actor): void
    {
        if ($user->is_active === $active) {
            return;
        }

        DB::transaction(function () use ($user, $active, $actor) {
            if ($active) {
                $this->ensureSingleActivePic($user->role, $user->department_id, $user->id);
            } else {
                $this->ensureNotSelfDowngrade($user, $actor, $user->role, false);
                $this->ensureSuperAdminRemains($user, $user->role, false);
                $this->ensureReceptionistReleased($user, $user->role, false);
            }

            $user->forceFill(['is_active' => $active])->save();

            $this->audit->record($active ? 'user.activate' : 'user.deactivate', $user, [], $actor);
        });
    }

    /**
     * Membuka kunci akun yang terkunci karena gagal login (AUTH-4).
     */
    public function unlock(User $user, User $actor): void
    {
        $user->forceFill(['failed_logins' => 0, 'locked_until' => null])->save();

        $this->audit->record('user.unlock', $user, [], $actor);
    }

    /**
     * USR-4: Super Admin tidak boleh menonaktifkan atau menurunkan peran dirinya sendiri.
     */
    private function ensureNotSelfDowngrade(User $user, User $actor, UserRole $newRole, bool $newActive): void
    {
        if ($user->is($actor) && ($newRole !== $user->role || ! $newActive)) {
            throw ValidationException::withMessages([
                'role' => 'Anda tidak dapat menonaktifkan atau menurunkan peran akun sendiri.',
            ]);
        }
    }

    /**
     * USR-4: jumlah Super Admin aktif tidak boleh menjadi nol.
     */
    private function ensureSuperAdminRemains(User $user, UserRole $newRole, bool $newActive): void
    {
        $isActiveSuperAdmin = $user->role === UserRole::SuperAdmin && $user->is_active;
        $staysActiveSuperAdmin = $newRole === UserRole::SuperAdmin && $newActive;

        if (! $isActiveSuperAdmin || $staysActiveSuperAdmin) {
            return;
        }

        $others = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->lockForUpdate()
            ->count();

        if ($others === 0) {
            throw ValidationException::withMessages(['role' => 'Harus tetap ada minimal satu Super Admin aktif.']);
        }
    }

    /**
     * USR-5: resepsionis yang masih memegang QR aktif/nonaktif tidak boleh dinonaktifkan atau diganti perannya.
     */
    private function ensureReceptionistReleased(User $user, UserRole $newRole, bool $newActive): void
    {
        if ($user->role !== UserRole::Receptionist || ($newRole === UserRole::Receptionist && $newActive)) {
            return;
        }

        $holdsQr = QrCode::query()
            ->where('receptionist_id', $user->id)
            ->whereIn('status', [QrStatus::Active, QrStatus::Inactive])
            ->exists();

        if ($holdsQr) {
            throw ValidationException::withMessages(['role' => 'Pindahkan QR ke akun resepsionis lain terlebih dahulu.']);
        }
    }

    /**
     * USR-5: satu departemen hanya boleh memiliki satu akun PIC aktif.
     */
    private function ensureSingleActivePic(UserRole $role, ?int $departmentId, ?int $exceptUserId): void
    {
        if ($role !== UserRole::Pic || $departmentId === null) {
            return;
        }

        $taken = User::query()
            ->where('role', UserRole::Pic)
            ->where('department_id', $departmentId)
            ->where('is_active', true)
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->lockForUpdate()
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['department_id' => 'Departemen ini sudah memiliki akun PIC aktif.']);
        }
    }

    /**
     * Menyamakan lokasi Admin (USR-2); lokasi yang tetap dipertahankan tidak diubah catatan penugasannya.
     *
     * @param  array<int, int|string>  $locationIds
     */
    private function syncLocations(User $user, array $locationIds, User $actor): void
    {
        $current = $user->locations()->pluck('locations.id')->map(fn ($id) => (int) $id)->all();
        $target = array_values(array_unique(array_map('intval', $locationIds)));

        $added = array_diff($target, $current);
        $removed = array_diff($current, $target);

        if ($added === [] && $removed === []) {
            return;
        }

        $user->locations()->detach($removed);
        $user->locations()->attach(
            array_fill_keys($added, ['assigned_by' => $actor->id, 'assigned_at' => now()]),
        );

        sort($target);
        $this->audit->record('user.assign_locations', $user, ['location_ids' => $target], $actor);
    }
}

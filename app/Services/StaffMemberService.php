<?php

namespace App\Services;

use App\Models\Department;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis daftar petugas departemen (PTG-1, PTG-2). Petugas tidak pernah dihapus
 * agar nama orang yang ditemui pada kunjungan lama tetap terbaca.
 */
class StaffMemberService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Menambah petugas di sebuah departemen.
     */
    public function create(Department $department, string $name, User $actor): StaffMember
    {
        $this->ensureUniqueName($department->id, $name, null);

        $staff = StaffMember::create(['department_id' => $department->id, 'name' => $name]);

        $this->audit->record('staff.create', $staff, ['department_id' => $department->id, 'name' => $name], $actor);

        return $staff;
    }

    /**
     * Mengubah nama petugas.
     */
    public function rename(StaffMember $staff, string $name, User $actor): StaffMember
    {
        $this->ensureUniqueName($staff->department_id, $name, $staff->id);

        $oldName = $staff->name;
        $staff->update(['name' => $name]);

        if ($oldName !== $name) {
            $this->audit->record('staff.update', $staff, ['from' => $oldName, 'to' => $name], $actor);
        }

        return $staff;
    }

    /**
     * Mengaktifkan atau menonaktifkan petugas; petugas nonaktif tidak muncul di pilihan orang yang ditemui.
     */
    public function setActive(StaffMember $staff, bool $active, User $actor): void
    {
        if ($staff->is_active === $active) {
            return;
        }

        $staff->forceFill(['is_active' => $active])->save();

        $this->audit->record($active ? 'staff.activate' : 'staff.deactivate', $staff, [], $actor);
    }

    /**
     * Nama petugas unik per departemen, tanpa membedakan huruf besar/kecil.
     */
    private function ensureUniqueName(int $departmentId, string $name, ?int $exceptId): void
    {
        $taken = StaffMember::query()
            ->where('department_id', $departmentId)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'Nama petugas sudah ada di departemen ini.']);
        }
    }
}

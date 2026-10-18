<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis departemen (DEP-1 sampai DEP-3). Departemen tidak pernah dihapus.
 */
class DepartmentService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Menambah departemen di sebuah lokasi.
     */
    public function create(Location $location, string $name, User $actor): Department
    {
        $this->ensureUniqueName($location->id, $name, null);

        $department = Department::create(['location_id' => $location->id, 'name' => $name]);

        $this->audit->record('department.create', $department, ['location_id' => $location->id, 'name' => $name], $actor);

        return $department;
    }

    /**
     * Mengubah nama departemen; lokasinya tidak dapat diubah (DEP-1).
     */
    public function rename(Department $department, string $name, User $actor): Department
    {
        $this->ensureUniqueName($department->location_id, $name, $department->id);

        $oldName = $department->name;
        $department->update(['name' => $name]);

        if ($oldName !== $name) {
            $this->audit->record('department.update', $department, ['from' => $oldName, 'to' => $name], $actor);
        }

        return $department;
    }

    /**
     * Mengaktifkan atau menonaktifkan departemen; penugasan yang sudah ada tetap berjalan (DEP-2).
     */
    public function setActive(Department $department, bool $active, User $actor): void
    {
        if ($department->is_active === $active) {
            return;
        }

        $department->forceFill(['is_active' => $active])->save();

        $this->audit->record($active ? 'department.activate' : 'department.deactivate', $department, [], $actor);
    }

    /**
     * Nama departemen unik per lokasi; perbandingan tidak membedakan huruf besar/kecil
     * karena collation utf8mb4_unicode_ci.
     */
    private function ensureUniqueName(int $locationId, string $name, ?int $exceptId): void
    {
        $taken = Department::query()
            ->where('location_id', $locationId)
            ->where('name', $name)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'Nama departemen sudah digunakan di lokasi ini.']);
        }
    }
}

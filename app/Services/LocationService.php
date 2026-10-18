<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis lokasi (LOK-1 sampai LOK-3).
 */
class LocationService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Membuat lokasi baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Location
    {
        $location = Location::create(['name' => $data['name'], 'description' => $data['description'] ?? null]);

        $this->audit->record('location.create', $location, ['name' => $location->name], $actor);

        return $location;
    }

    /**
     * Mengubah nama dan deskripsi lokasi.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Location $location, array $data, User $actor): Location
    {
        $location->fill(['name' => $data['name'], 'description' => $data['description'] ?? null]);
        $changedFields = array_keys($location->getDirty());
        $location->save();

        if ($changedFields !== []) {
            $this->audit->record('location.update', $location, ['fields' => $changedFields], $actor);
        }

        return $location;
    }

    /**
     * Mengaktifkan atau menonaktifkan lokasi. Kunjungan aktif tetap dapat diselesaikan (LOK-2).
     */
    public function setActive(Location $location, bool $active, User $actor): void
    {
        if ($location->is_active === $active) {
            return;
        }

        $location->forceFill(['is_active' => $active])->save();

        $this->audit->record($active ? 'location.activate' : 'location.deactivate', $location, [], $actor);
    }

    /**
     * Menghapus (soft delete) lokasi yang belum memiliki data (LOK-3).
     */
    public function delete(Location $location, User $actor): void
    {
        if ($this->hasData($location)) {
            throw ValidationException::withMessages([
                'location' => 'Lokasi yang sudah memiliki kunjungan, departemen, atau QR tidak dapat dihapus, hanya dinonaktifkan.',
            ]);
        }

        $location->delete();

        $this->audit->record('location.delete', $location, ['name' => $location->name], $actor);
    }

    /**
     * Mengecek apakah lokasi sudah memiliki kunjungan, departemen, atau QR.
     */
    public function hasData(Location $location): bool
    {
        return $location->guestVisits()->exists()
            || $location->departments()->exists()
            || $location->qrCodes()->exists();
    }
}

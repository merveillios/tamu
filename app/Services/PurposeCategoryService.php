<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\User;

/**
 * Aturan bisnis kategori keperluan (KAT-1 sampai KAT-3). Kategori tidak pernah dihapus.
 */
class PurposeCategoryService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Menambah kategori di sebuah lokasi.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Location $location, array $data, User $actor): PurposeCategory
    {
        $category = PurposeCategory::create([
            'location_id' => $location->id,
            'name' => $data['name'],
            'suggested_department_id' => $data['suggested_department_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->audit->record('purpose_category.create', $category, ['location_id' => $location->id, 'name' => $category->name], $actor);

        return $category;
    }

    /**
     * Mengubah nama, saran departemen, dan urutan; lokasi kategori tetap.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PurposeCategory $category, array $data, User $actor): PurposeCategory
    {
        $category->fill([
            'name' => $data['name'],
            'suggested_department_id' => $data['suggested_department_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $changedFields = array_keys($category->getDirty());
        $category->save();

        if ($changedFields !== []) {
            $this->audit->record('purpose_category.update', $category, ['fields' => $changedFields], $actor);
        }

        return $category;
    }

    /**
     * Mengaktifkan atau menonaktifkan kategori (KAT-2). Kunjungan lama tetap
     * menampilkan nama kategori karena tersimpan sebagai salinan.
     */
    public function setActive(PurposeCategory $category, bool $active, User $actor): void
    {
        if ($category->is_active === $active) {
            return;
        }

        $category->forceFill(['is_active' => $active])->save();

        $this->audit->record($active ? 'purpose_category.activate' : 'purpose_category.deactivate', $category, [], $actor);
    }
}

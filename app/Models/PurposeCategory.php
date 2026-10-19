<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\LimitedByAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Kategori keperluan tamu per lokasi, dengan saran departemen (KAT-1).
 */
class PurposeCategory extends Model
{
    use CannotBeDeleted;
    use LimitedByAccess;

    protected $fillable = [
        'location_id',
        'name',
        'suggested_department_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Lokasi kategori ini.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Departemen yang disarankan untuk kategori ini.
     */
    public function suggestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'suggested_department_id');
    }

    /**
     * Nama departemen tempat kategori dikelompokkan, atau null bila tanpa departemen aktif (grup Umum).
     */
    public function groupName(): ?string
    {
        $department = $this->suggestedDepartment;

        return $department !== null && $department->is_active ? $department->name : null;
    }

    /**
     * Mengelompokkan kategori per departemen untuk formulir tamu dan halaman admin.
     * Grup urut nama departemen, grup Umum (kunci '') paling bawah; urutan di dalam grup mengikuti masukan.
     *
     * @param  Collection<int, self>  $categories
     * @return Collection<string, Collection<int, self>>
     */
    public static function groupByDepartment(Collection $categories): Collection
    {
        // toBase(): except() pada koleksi Eloquent memfilter ID model, bukan kunci grup.
        $groups = $categories->toBase()->groupBy(fn (self $category) => $category->groupName() ?? '')
            ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

        return $groups->has('') ? $groups->except('')->put('', $groups->get('')) : $groups;
    }
}

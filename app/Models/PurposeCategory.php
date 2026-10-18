<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kategori keperluan tamu per lokasi, dengan saran departemen (KAT-1).
 */
class PurposeCategory extends Model
{
    use CannotBeDeleted;

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
}

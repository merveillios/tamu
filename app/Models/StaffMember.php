<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Petugas departemen yang dapat ditemui tamu (PTG-1).
 */
class StaffMember extends Model
{
    use CannotBeDeleted;

    protected $fillable = [
        'department_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Departemen petugas ini.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

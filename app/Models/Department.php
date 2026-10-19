<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\LimitedByAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Departemen atau bidang tujuan penugasan kunjungan.
 */
class Department extends Model
{
    use CannotBeDeleted;
    use LimitedByAccess;

    protected $fillable = [
        'location_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Lokasi tempat departemen berada.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Petugas yang dapat ditemui tamu di departemen ini.
     */
    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    /**
     * QR Code yang diarahkan ke departemen ini.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Akun PIC departemen ini.
     */
    public function picUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

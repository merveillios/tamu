<?php

namespace App\Models;

use App\Enums\QrStatus;
use App\Models\Concerns\LimitedByAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QR Code titik masuk; setiap QR terhubung ke satu lokasi, satu departemen, dan satu akun resepsionis.
 */
class QrCode extends Model
{
    use LimitedByAccess;

    public const UPDATED_AT = null;

    protected $fillable = [
        'location_id',
        'department_id',
        'receptionist_id',
        'token',
        'label',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => QrStatus::class,
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * Lokasi QR ini.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Departemen tujuan QR ini; kosong untuk QR yang dibuat sebelum kolom ini ada.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Akun resepsionis pemilik QR ini.
     */
    public function receptionist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptionist_id');
    }

    /**
     * Akun yang membuat QR ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Kunjungan yang tercatat lewat QR ini.
     */
    public function guestVisits(): HasMany
    {
        return $this->hasMany(GuestVisit::class);
    }
}

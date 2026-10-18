<?php

namespace App\Models;

use App\Models\Concerns\LimitedByAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lokasi atau gedung instansi tempat tamu berkunjung.
 */
class Location extends Model
{
    use LimitedByAccess;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Departemen di lokasi ini.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * QR Code titik masuk di lokasi ini.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Kategori keperluan yang tampil di formulir tamu lokasi ini.
     */
    public function purposeCategories(): HasMany
    {
        return $this->hasMany(PurposeCategory::class);
    }

    /**
     * Kunjungan yang tercatat di lokasi ini.
     */
    public function guestVisits(): HasMany
    {
        return $this->hasMany(GuestVisit::class);
    }

    /**
     * Akun Admin yang ditugaskan ke lokasi ini.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_locations')
            ->withPivot('assigned_by', 'assigned_at');
    }
}

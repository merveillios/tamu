<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\CannotBeUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu unggahan foto KTP atau selfie milik tamu, dikenali dari email Google (TAM-18).
 *
 * Tidak pernah diubah atau dihapus: mengganti foto berarti menambah baris baru, dan
 * yang dipakai selalu baris terbaru per email dan jenis.
 */
class GuestDocument extends Model
{
    use CannotBeDeleted;
    use CannotBeUpdated;

    public const UPDATED_AT = null;

    /**
     * Jenis foto beserta labelnya.
     */
    public const TYPES = [
        'ktp' => 'Foto KTP',
        'selfie' => 'Foto selfie',
    ];

    protected $fillable = [
        'email',
        'type',
        'image',
        'mime_type',
        'width',
        'height',
        'size_bytes',
        'sha256',
        'qr_code_id',
        'ip_address',
    ];

    protected $hidden = [
        'image',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'size_bytes' => 'integer',
    ];

    /**
     * QR tempat foto diunggah.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /**
     * Label jenis foto.
     */
    public function label(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}

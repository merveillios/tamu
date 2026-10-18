<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\CannotBeUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gambar tanda tangan tamu untuk satu kunjungan (TAM-9).
 */
class VisitSignature extends Model
{
    use CannotBeDeleted;
    use CannotBeUpdated;

    public const UPDATED_AT = null;

    protected $primaryKey = 'visit_id';

    public $incrementing = false;

    protected $fillable = [
        'visit_id',
        'image',
        'mime_type',
        'size_bytes',
        'sha256',
    ];

    protected $hidden = [
        'image',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * Kunjungan pemilik tanda tangan.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(GuestVisit::class, 'visit_id');
    }
}

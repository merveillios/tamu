<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\CannotBeUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan audit aksi penting staf (AUD-1). Tidak dapat diubah atau dihapus.
 */
class AuditLog extends Model
{
    use CannotBeDeleted;
    use CannotBeUpdated;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'detail',
    ];

    protected $casts = [
        'detail' => 'array',
    ];

    /**
     * Akun pelaku aksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

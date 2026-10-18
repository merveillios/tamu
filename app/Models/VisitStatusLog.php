<?php

namespace App\Models;

use App\Enums\VisitStatus;
use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\CannotBeUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan satu perubahan status kunjungan untuk timeline (Bab 7).
 */
class VisitStatusLog extends Model
{
    use CannotBeDeleted;
    use CannotBeUpdated;

    public const UPDATED_AT = null;

    protected $fillable = [
        'visit_id',
        'location_id',
        'from_status',
        'to_status',
        'changed_by',
        'staff_member_id',
        'note',
    ];

    protected $casts = [
        'from_status' => VisitStatus::class,
        'to_status' => VisitStatus::class,
    ];

    /**
     * Kunjungan yang statusnya berubah.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(GuestVisit::class, 'visit_id');
    }

    /**
     * Akun yang mengubah status; kosong bila dibuat oleh tamu.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Salinan orang yang ditemui saat aksi PIC.
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}

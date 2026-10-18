<?php

namespace App\Models;

use App\Models\Concerns\CannotBeDeleted;
use App\Models\Concerns\CannotBeUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kali penugasan kunjungan ke departemen dan orang yang ditemui (RSP-2, RSP-3).
 */
class VisitAssignment extends Model
{
    use CannotBeDeleted;
    use CannotBeUpdated;

    public $timestamps = false;

    protected $fillable = [
        'visit_id',
        'department_id',
        'assigned_by',
        'assigned_at',
        'staff_member_id',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Kunjungan yang ditugaskan.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(GuestVisit::class, 'visit_id');
    }

    /**
     * Departemen tujuan penugasan.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Orang yang ditemui yang dipilih pada penugasan ini.
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /**
     * Akun resepsionis yang menugaskan.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

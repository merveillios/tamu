<?php

namespace App\Models;

use App\Enums\VisitStatus;
use App\Enums\VulnerableGroup;
use App\Models\Concerns\CannotBeDeleted;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu kunjungan tamu beserta data proses penugasan dan hasilnya.
 */
class GuestVisit extends Model
{
    use CannotBeDeleted;

    public $timestamps = false;

    /**
     * Kolom yang diisi saat kunjungan dibuat (TAM-4). Perubahan status dan
     * penugasan dilakukan lewat update bersyarat di VisitService (Bab 7.2).
     */
    protected $fillable = [
        'location_id',
        'qr_code_id',
        'visit_date',
        'daily_number',
        'email',
        'email_verified_at',
        'purpose_category_id',
        'purpose_category_name',
        'suggested_department_id',
        'purpose',
        'vulnerable_group',
        'name',
        'organization',
        'phone',
        'address',
        'status',
        'check_in_at',
        'consent_at',
        'ip_address',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'daily_number' => 'integer',
        'email_verified_at' => 'datetime',
        'vulnerable_group' => VulnerableGroup::class,
        'status' => VisitStatus::class,
        'check_in_at' => 'datetime',
        'assigned_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'consent_at' => 'datetime',
    ];

    /**
     * Label dan warna badge untuk UI, termasuk label "Menunggu PIC" yang
     * bukan status database (Bab 0.4).
     */
    protected function displayStatus(): Attribute
    {
        return Attribute::get(function (): array {
            if ($this->status === VisitStatus::Waiting && $this->assigned_department_id !== null) {
                return ['label' => 'Menunggu PIC', 'badge' => 'info'];
            }

            return ['label' => $this->status->label(), 'badge' => $this->status->badge()];
        });
    }

    /**
     * Lokasi kunjungan.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * QR Code yang dipindai tamu.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    /**
     * Kategori keperluan yang dipilih tamu.
     */
    public function purposeCategory(): BelongsTo
    {
        return $this->belongsTo(PurposeCategory::class);
    }

    /**
     * Salinan saran departemen dari kategori saat kunjungan dibuat.
     */
    public function suggestedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'suggested_department_id');
    }

    /**
     * Departemen tujuan saat ini.
     */
    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    /**
     * Orang yang ditemui, dipilih resepsionis saat menugaskan.
     */
    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'served_by_staff_id');
    }

    /**
     * Akun resepsionis yang terakhir menugaskan.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Akun PIC yang menerima penugasan.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Akun yang menyelesaikan atau membatalkan kunjungan.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Riwayat penugasan kunjungan.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VisitAssignment::class, 'visit_id');
    }

    /**
     * Riwayat perubahan status kunjungan.
     */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(VisitStatusLog::class, 'visit_id');
    }

    /**
     * Gambar tanda tangan tamu.
     */
    public function signature(): HasOne
    {
        return $this->hasOne(VisitSignature::class, 'visit_id');
    }
}

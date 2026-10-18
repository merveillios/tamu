<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun staf: Super Admin, Admin, Resepsionis, atau PIC.
 */
class User extends Authenticatable
{
    use HasFactory;

    /**
     * Tabel users tidak memiliki kolom remember_token (PRD Bab 9.2).
     *
     * @var string
     */
    protected $rememberTokenName = '';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'role' => UserRole::class,
        'is_active' => 'boolean',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Departemen akun PIC.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Lokasi yang ditugaskan ke akun Admin.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'user_locations')
            ->withPivot('assigned_by', 'assigned_at');
    }

    /**
     * QR Code yang terhubung ke akun Resepsionis.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'receptionist_id');
    }

    /**
     * Mengecek apakah akun memiliki salah satu peran yang diberikan.
     */
    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}

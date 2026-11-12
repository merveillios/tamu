<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pilihan kelompok rentan pada formulir tamu, dikelola Admin dan Super Admin (VUL-1 sampai VUL-3).
 *
 * Kunjungan menyimpan `code`, bukan ID, sehingga kode tidak pernah diubah setelah dibuat.
 * `priority` terisi berarti tamu kelompok ini didahulukan di antrean; angka kecil lebih dulu.
 */
class VulnerableGroup extends Model
{
    protected $fillable = [
        'code',
        'name',
        'priority',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'priority' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Kunjungan yang memilih kelompok ini.
     */
    public function visits(): HasMany
    {
        return $this->hasMany(GuestVisit::class, 'vulnerable_group', 'code');
    }

    /**
     * Urutan tampil di dropdown dan daftar admin.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Pilihan formulir tamu: kelompok aktif, ditambah kelompok yang sedang dipakai
     * tamu lama walau sudah nonaktif agar dropdown terkunci tetap menampilkan nilainya.
     */
    public function scopeForGuestForm(Builder $query, ?string $currentCode = null): Builder
    {
        return $query
            ->where(fn (Builder $inner) => $inner
                ->where('is_active', true)
                ->when($currentCode, fn (Builder $or) => $or->orWhere('code', $currentCode)))
            ->ordered();
    }
}

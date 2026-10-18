<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menyediakan local scope forUser() yang mendelegasikan cakupan ke AccessScope.
 *
 * Sengaja bukan global scope karena halaman tamu harus membaca data tanpa
 * filter staf (PRD Bab 12.2).
 */
trait LimitedByAccess
{
    /**
     * Membatasi query ke data dalam cakupan akun staf.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return app(AccessScope::class)->apply($query, $user);
    }
}

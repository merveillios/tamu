<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Menolak penghapusan baris lewat Eloquent (PRD Bab 9.2).
 */
trait CannotBeDeleted
{
    /**
     * Mendaftarkan event deleting yang selalu menolak penghapusan.
     */
    public static function bootCannotBeDeleted(): void
    {
        static::deleting(function ($model) {
            throw new LogicException(class_basename($model).' tidak boleh dihapus.');
        });
    }
}

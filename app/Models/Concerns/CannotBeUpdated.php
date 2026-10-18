<?php

namespace App\Models\Concerns;

use LogicException;

/**
 * Menolak perubahan baris lewat Eloquent untuk catatan yang bersifat tetap (PRD Bab 9.2).
 */
trait CannotBeUpdated
{
    /**
     * Mendaftarkan event updating yang selalu menolak perubahan.
     */
    public static function bootCannotBeUpdated(): void
    {
        static::updating(function ($model) {
            throw new LogicException(class_basename($model).' tidak boleh diubah.');
        });
    }
}

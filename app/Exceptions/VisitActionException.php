<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Penolakan aksi kunjungan: 409 bila status sudah diubah petugas lain, 422 bila transisi
 * tidak diizinkan (PRD Bab 7, Bab 10.9). Dirender sebagai JSON AJAX Bab 10.
 */
class VisitActionException extends RuntimeException
{
    public function __construct(
        private readonly int $status,
        private readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    /**
     * Status tamu sudah diubah petugas lain (Bab 7.2).
     */
    public static function conflict(string $message = 'Status tamu ini sudah diubah oleh petugas lain. Daftar akan dimuat ulang.'): self
    {
        return new self(409, 'STATUS_CONFLICT', $message);
    }

    /**
     * Transisi status atau penugasan tidak diizinkan (Bab 7.1).
     */
    public static function invalidTransition(string $message = 'Aksi ini tidak dapat dilakukan pada status kunjungan saat ini.'): self
    {
        return new self(422, 'INVALID_TRANSITION', $message);
    }

    /**
     * Respons JSON sesuai konvensi AJAX Bab 10.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'error' => ['code' => $this->errorCode, 'message' => $this->getMessage()],
        ], $this->status);
    }
}

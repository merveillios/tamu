<?php

namespace App\Exceptions;

use App\Services\GuestQrResolver;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Input yang tidak pernah dikembalikan ke session saat validasi gagal. Data URL
     * tanda tangan tidak boleh dimasukkan kembali ke HTML (Bab 12.1).
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'signature',
        'form_token',
        'credential',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Validasi AJAX staf memakai konvensi respons JSON Bab 10.
        $this->renderable(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson() || ! $request->is('admin/*')) {
                return null;
            }

            return response()->json([
                'ok' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => collect($e->errors())->flatten()->first()],
                'errors' => $e->errors(),
            ], 422);
        });

        // QR tidak aktif menampilkan halaman tamu dengan pesan seragam TAM-6.
        $this->renderable(function (HttpException $e) {
            if ($e->getStatusCode() === 404 && $e->getMessage() === GuestQrResolver::INACTIVE_MESSAGE) {
                return response()->view('guest.inactive', ['message' => $e->getMessage()], 404);
            }

            return null;
        });
    }
}

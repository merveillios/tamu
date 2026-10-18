<?php

namespace App\Services;

use App\Models\QrCode;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Carbon;

/**
 * Penyimpanan hasil verifikasi email tamu dan nonce Google di session, terikat per QR (TAM-11).
 */
class GuestSessionService
{
    public function __construct(private readonly Session $session)
    {
    }

    /**
     * Membuat nonce acak baru untuk satu percobaan verifikasi Google.
     */
    public function newNonce(QrCode $qr): string
    {
        $nonce = bin2hex(random_bytes(16));
        $this->session->put($this->key($qr, 'nonce'), $nonce);

        return $nonce;
    }

    /**
     * Mengambil lalu menghapus nonce agar satu nonce hanya bisa dipakai sekali.
     */
    public function pullNonce(QrCode $qr): string
    {
        return (string) $this->session->pull($this->key($qr, 'nonce'), '');
    }

    /**
     * Menyimpan email terverifikasi untuk QR ini.
     */
    public function rememberEmail(QrCode $qr, string $email): void
    {
        $this->session->put($this->key($qr, 'email'), [
            'email' => $email,
            'verified_at' => now()->timestamp,
        ]);
    }

    /**
     * Email terverifikasi yang belum kedaluwarsa, atau null.
     */
    public function verifiedEmail(QrCode $qr): ?string
    {
        $entry = $this->session->get($this->key($qr, 'email'));

        if (! is_array($entry) || $this->isExpired($entry)) {
            return null;
        }

        return $entry['email'];
    }

    /**
     * Waktu verifikasi email untuk kolom email_verified_at.
     */
    public function verifiedAt(QrCode $qr): ?Carbon
    {
        $entry = $this->session->get($this->key($qr, 'email'));

        return is_array($entry) ? Carbon::createFromTimestamp($entry['verified_at']) : null;
    }

    /**
     * Menghapus hasil verifikasi (tombol Ganti akun atau kedaluwarsa).
     */
    public function forgetEmail(QrCode $qr): void
    {
        $this->session->forget($this->key($qr, 'email'));
    }

    /**
     * Membuat token sekali pakai untuk satu render formulir (TAM-14).
     */
    public function newFormToken(QrCode $qr): string
    {
        $token = bin2hex(random_bytes(16));
        $this->session->put($this->key($qr, 'form_token'), $token);

        return $token;
    }

    /**
     * Mengambil dan menghapus token formulir, lalu mencocokkannya dengan kiriman.
     */
    public function consumeFormToken(QrCode $qr, ?string $submitted): bool
    {
        $expected = (string) $this->session->pull($this->key($qr, 'form_token'), '');

        return $expected !== '' && is_string($submitted) && hash_equals($expected, $submitted);
    }

    /**
     * Mengecek masa berlaku verifikasi email.
     *
     * @param  array{email: string, verified_at: int}  $entry
     */
    private function isExpired(array $entry): bool
    {
        $ttlSeconds = config('sitamu.guest.email_verify_ttl_minutes') * 60;

        return now()->timestamp - $entry['verified_at'] > $ttlSeconds;
    }

    /**
     * Kunci session per QR.
     */
    private function key(QrCode $qr, string $name): string
    {
        return "guest.{$qr->id}.{$name}";
    }
}

<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\PurposeCategory;
use App\Models\QrCode;

/**
 * Bantuan tes halaman tamu: QR siap pakai, email terverifikasi di session, dan tanda tangan.
 */
trait InteractsWithGuestPages
{
    use CreatesSitamuData;

    /**
     * Membuat QR aktif beserta satu kategori aktif di lokasinya.
     *
     * @return array{0: QrCode, 1: PurposeCategory}
     */
    protected function usableQr(): array
    {
        $location = $this->createLocation();
        $qr = $this->createQr($location, $this->createUser(UserRole::Receptionist));

        return [$qr, $this->createCategory($location, $this->createDepartment($location))];
    }

    /**
     * Menyimpan email terverifikasi di session untuk QR tertentu.
     */
    protected function verifiedAs(QrCode $qr, string $email, ?int $verifiedAt = null): static
    {
        return $this->withSession([
            "guest.{$qr->id}.email" => ['email' => $email, 'verified_at' => $verifiedAt ?? now()->timestamp],
        ]);
    }

    /**
     * Data URL PNG tanda tangan yang valid: garis diagonal di kanvas transparan 400x150.
     */
    protected function signatureDataUrl(int $width = 400, int $height = 150, bool $withInk = true): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        if ($withInk) {
            imagesetthickness($image, 3);
            imageline($image, 20, 20, $width - 20, $height - 20, imagecolorallocate($image, 17, 24, 39));
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Mengambil token formulir dengan membuka halaman formulir terlebih dahulu.
     */
    protected function formToken(QrCode $qr): string
    {
        $this->get("/tamu/{$qr->token}/formulir")->assertOk();

        return session("guest.{$qr->id}.form_token");
    }

    /**
     * Isian formulir tamu yang valid.
     *
     * @return array<string, mixed>
     */
    protected function guestPayload(PurposeCategory $category, string $formToken, array $overrides = []): array
    {
        return array_merge([
            'form_token' => $formToken,
            'purpose_category_id' => $category->id,
            'purpose' => 'Konsultasi perizinan perdagangan',
            'vulnerable_group' => 'bukan',
            'name' => 'Siti Aminah',
            'organization' => 'CV Maju Jaya',
            'phone' => '0812-3456-7890',
            'address' => 'Jl. Ahmad Yani No. 1, Surabaya',
            'signature' => $this->signatureDataUrl(),
            'consent' => '1',
        ], $overrides);
    }
}

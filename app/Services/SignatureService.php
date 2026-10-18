<?php

namespace App\Services;

use GdImage;

/**
 * Validasi dan normalisasi gambar tanda tangan tamu (TAM-9, PRD Bab 11.1).
 *
 * Gambar disimpan sebagai PNG hasil re-encode GD, bukan data asli dari browser.
 */
class SignatureService
{
    private const PREFIX = 'data:image/png;base64,';

    private const MIN_WIDTH = 200;

    private const MAX_WIDTH = 1200;

    private const MIN_HEIGHT = 80;

    private const MAX_HEIGHT = 600;

    /**
     * Jumlah minimal piksel goresan agar kotak kosong atau titik tunggal ditolak.
     */
    private const MIN_INK_PIXELS = 50;

    /**
     * Mengubah data URL menjadi PNG yang sudah di-re-encode, atau null bila tidak valid.
     */
    public function normalize(?string $dataUrl): ?string
    {
        $image = $this->decode($dataUrl);

        if ($image === null) {
            return null;
        }

        imagesavealpha($image, true);
        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png === '' ? null : $png;
    }

    /**
     * Mengecek apakah data URL memenuhi semua aturan tanda tangan.
     */
    public function isValid(?string $dataUrl): bool
    {
        $image = $this->decode($dataUrl);

        if ($image === null) {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Membaca data URL menjadi gambar GD bila prefiks, ukuran, dimensi, dan isi goresan valid.
     */
    private function decode(?string $dataUrl): ?GdImage
    {
        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, self::PREFIX)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strlen(self::PREFIX)), true);

        if ($binary === false || $binary === '' || strlen($binary) > config('sitamu.signature_max_kb') * 1024) {
            return null;
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return null;
        }

        if (! $this->hasValidDimensions($image) || ! $this->hasEnoughInk($image)) {
            imagedestroy($image);

            return null;
        }

        return $image;
    }

    /**
     * Lebar 200–1200 px dan tinggi 80–600 px.
     */
    private function hasValidDimensions(GdImage $image): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        return $width >= self::MIN_WIDTH && $width <= self::MAX_WIDTH
            && $height >= self::MIN_HEIGHT && $height <= self::MAX_HEIGHT;
    }

    /**
     * Minimal 50 piksel yang tidak transparan dan tidak putih.
     */
    private function hasEnoughInk(GdImage $image): bool
    {
        $ink = 0;
        $width = imagesx($image);
        $height = imagesy($image);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                $isTransparent = $rgba['alpha'] >= 120;
                $isWhite = $rgba['red'] > 240 && $rgba['green'] > 240 && $rgba['blue'] > 240;

                if (! $isTransparent && ! $isWhite && ++$ink >= self::MIN_INK_PIXELS) {
                    return true;
                }
            }
        }

        return false;
    }
}

<?php

namespace App\Services;

use App\Models\GuestDocument;
use App\Models\QrCode;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Foto KTP dan selfie tamu dari "Lengkapi profil" di riwayat tamu (TAM-18, revisi HRD #4).
 *
 * Foto dibaca ulang dengan GD lalu disimpan sebagai JPEG baru, sehingga metadata (EXIF,
 * lokasi GPS) dan isi selain gambar tidak ikut tersimpan. Foto tidak pernah dihapus;
 * mengganti foto menambah baris baru.
 */
class GuestDocumentService
{
    /**
     * Sisi terpendek minimal agar tulisan di KTP masih terbaca.
     */
    private const MIN_SIDE = 300;

    /**
     * Mutu JPEG hasil simpan ulang.
     */
    private const JPEG_QUALITY = 80;

    /**
     * Data URL yang diterima dari kompresi di browser.
     */
    private const DATA_URL_PATTERN = '#^data:image/(jpeg|png|webp);base64,#';

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Menyimpan foto baru untuk email ini. Sumbernya data URL hasil kompresi browser,
     * atau file asli bila JavaScript tidak berjalan.
     */
    public function store(QrCode $qr, string $email, string $type, ?string $dataUrl, ?UploadedFile $file, ?string $ip): GuestDocument
    {
        $binary = $this->binaryFrom($dataUrl, $file);
        $image = $binary === null ? false : @imagecreatefromstring($binary);

        if ($image === false) {
            throw ValidationException::withMessages(['photo' => 'File bukan foto yang valid. Gunakan foto JPG, PNG, atau WebP.']);
        }

        $image = $this->fit($image);

        if (min(imagesx($image), imagesy($image)) < self::MIN_SIDE) {
            imagedestroy($image);

            throw ValidationException::withMessages(['photo' => 'Foto terlalu kecil. Ambil foto lebih dekat atau gunakan resolusi lebih besar.']);
        }

        $jpeg = $this->encode($image);

        if (strlen($jpeg) > config('sitamu.guest.document_max_kb') * 1024) {
            throw ValidationException::withMessages(['photo' => 'Foto terlalu besar setelah dikompres. Coba foto lain.']);
        }

        [$width, $height] = getimagesizefromstring($jpeg);

        $document = GuestDocument::create([
            'email' => $email,
            'type' => $type,
            'image' => $jpeg,
            'mime_type' => 'image/jpeg',
            'width' => $width,
            'height' => $height,
            'size_bytes' => strlen($jpeg),
            'sha256' => hash('sha256', $jpeg),
            'qr_code_id' => $qr->id,
            'ip_address' => $ip,
        ]);

        // Email tidak ditulis ke detail audit; baris dokumen sudah menyimpannya.
        $this->audit->record('guest_document.upload', $document, ['type' => $type, 'qr_code_id' => $qr->id]);

        return $document;
    }

    /**
     * Foto terbaru per jenis untuk email ini, tanpa kolom gambar.
     *
     * @return Collection<string, GuestDocument>
     */
    public function latest(string $email): Collection
    {
        // Satu email hanya punya sedikit baris; keyBy() menyisakan baris terakhir (terbaru) per jenis.
        return GuestDocument::query()
            ->where('email', $email)
            ->orderBy('id')
            ->get(['id', 'email', 'type', 'width', 'height', 'size_bytes', 'created_at'])
            ->keyBy('type');
    }

    /**
     * Foto terbaru satu jenis beserta gambarnya, atau null bila belum pernah diunggah.
     */
    public function latestImage(string $email, string $type): ?GuestDocument
    {
        return GuestDocument::query()
            ->where('email', $email)
            ->where('type', $type)
            ->latest('id')
            ->first();
    }

    /**
     * Isi biner foto dari data URL atau file unggahan.
     */
    private function binaryFrom(?string $dataUrl, ?UploadedFile $file): ?string
    {
        if (is_string($dataUrl) && preg_match(self::DATA_URL_PATTERN, $dataUrl, $match) === 1) {
            $binary = base64_decode(substr($dataUrl, strlen($match[0])), true);

            return $binary === false || $binary === '' ? null : $binary;
        }

        if ($file !== null && $file->isValid()) {
            return (string) file_get_contents($file->getRealPath());
        }

        return null;
    }

    /**
     * Memperkecil gambar agar sisi terpanjang tidak lebih dari batas konfigurasi.
     */
    private function fit(GdImage $image): GdImage
    {
        $max = (int) config('sitamu.guest.document_max_side');
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $max / max($width, $height));

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
        imagedestroy($image);

        return $resized;
    }

    /**
     * Menyimpan ulang sebagai JPEG di atas latar putih (PNG transparan tidak menjadi hitam).
     */
    private function encode(GdImage $image): string
    {
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $jpeg = (string) ob_get_clean();
        imagedestroy($canvas);

        return $jpeg;
    }
}

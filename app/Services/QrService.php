<?php

namespace App\Services;

use App\Enums\QrStatus;
use App\Models\Location;
use App\Models\QrCode;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pembuatan, perubahan status, regenerate, dan gambar QR Code (QR-1 sampai QR-6).
 */
class QrService
{
    /**
     * Ukuran sisi gambar QR dalam piksel, minimal 600 sesuai QR-5.
     */
    private const IMAGE_SIZE = 600;

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * URL halaman tamu yang dikodekan di QR. Selalu dibentuk dari APP_URL,
     * bukan dari host request, agar QR yang dicetak menunjuk domain resmi.
     */
    public function url(QrCode $qr): string
    {
        return rtrim(config('app.url'), '/').'/tamu/'.$qr->token;
    }

    /**
     * Membuat QR baru dengan token acak untuk lokasi dan akun resepsionis tertentu (QR-1, QR-2).
     */
    public function create(Location $location, User $receptionist, ?string $label, User $actor): QrCode
    {
        $qr = QrCode::create([
            'location_id' => $location->id,
            'receptionist_id' => $receptionist->id,
            'token' => $this->newToken(),
            'label' => $label,
            'status' => QrStatus::Active,
            'created_by' => $actor->id,
        ]);

        $this->audit->record('qr.create', $qr, ['location_id' => $location->id, 'receptionist_id' => $receptionist->id], $actor);

        return $qr;
    }

    /**
     * Mengaktifkan atau menonaktifkan QR; QR yang dicabut tidak dapat diaktifkan lagi (QR-3).
     */
    public function setActive(QrCode $qr, bool $active, User $actor): void
    {
        $this->ensureNotRevoked($qr);

        $target = $active ? QrStatus::Active : QrStatus::Inactive;

        if ($qr->status === $target) {
            return;
        }

        $qr->forceFill(['status' => $target])->save();

        $this->audit->record($active ? 'qr.activate' : 'qr.deactivate', $qr, [], $actor);
    }

    /**
     * Membuat QR pengganti dengan token baru lalu mencabut QR lama dalam satu transaksi (QR-4).
     */
    public function regenerate(QrCode $qr, User $actor): QrCode
    {
        $this->ensureNotRevoked($qr);

        return DB::transaction(function () use ($qr, $actor) {
            $replacement = QrCode::create([
                'location_id' => $qr->location_id,
                'receptionist_id' => $qr->receptionist_id,
                'token' => $this->newToken(),
                'label' => $qr->label,
                'status' => QrStatus::Active,
                'created_by' => $actor->id,
            ]);

            $qr->forceFill(['status' => QrStatus::Revoked, 'revoked_at' => now()])->save();

            $this->audit->record('qr.regenerate', $replacement, ['old_qr_id' => $qr->id], $actor);

            return $replacement;
        });
    }

    /**
     * Memindahkan QR ke akun resepsionis lain; kunjungan aktif ikut berpindah karena
     * cakupan resepsionis dihitung dari kepemilikan QR (QR-6).
     */
    public function changeReceptionist(QrCode $qr, User $receptionist, User $actor): void
    {
        $previousId = $qr->receptionist_id;

        if ($previousId === $receptionist->id) {
            return;
        }

        $qr->forceFill(['receptionist_id' => $receptionist->id])->save();

        $this->audit->record('qr.change_receptionist', $qr, ['from' => $previousId, 'to' => $receptionist->id], $actor);
    }

    /**
     * Gambar QR dalam format png atau svg.
     */
    public function image(QrCode $qr, string $format): string
    {
        return $this->build($qr, $format === 'svg' ? new SvgWriter() : new PngWriter())->getString();
    }

    /**
     * Gambar QR SVG sebagai data URI untuk halaman cetak.
     */
    public function svgDataUri(QrCode $qr): string
    {
        return $this->build($qr, new SvgWriter())->getDataUri();
    }

    /**
     * Mencatat unduhan file QR di audit log (QR-5).
     */
    public function recordDownload(QrCode $qr, string $format, User $actor): void
    {
        $this->audit->record('qr.download', $qr, ['format' => $format], $actor);
    }

    /**
     * Membangun gambar QR dengan koreksi galat tinggi agar tetap terbaca walau cetakan rusak sebagian.
     *
     * Encoding ISO-8859-1 dipakai karena URL selalu ASCII; dengan UTF-8 (bawaan library) QR
     * menyisipkan penanda ECI yang gagal dibaca sebagian aplikasi pemindai.
     */
    private function build(QrCode $qr, WriterInterface $writer): ResultInterface
    {
        return Builder::create()
            ->writer($writer)
            ->encoding(new Encoding('ISO-8859-1'))
            ->data($this->url($qr))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(self::IMAGE_SIZE)
            ->margin(24)
            ->build();
    }

    /**
     * Token 64 karakter hex yang tidak diturunkan dari ID apa pun (QR-1).
     */
    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Menolak perubahan pada QR yang sudah dicabut.
     */
    private function ensureNotRevoked(QrCode $qr): void
    {
        if ($qr->status === QrStatus::Revoked) {
            throw ValidationException::withMessages(['qr' => 'QR yang sudah dicabut tidak dapat diubah atau diaktifkan kembali.']);
        }
    }
}

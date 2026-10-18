<?php

/*
 * Proses pekerja untuk tes kiriman bersamaan (tests/Feature/ConcurrencyTest.php).
 *
 * Setiap proses mem-boot Laravel dengan koneksi database sendiri, menunggu sampai waktu
 * mulai yang sama, lalu menjalankan satu aksi agar benar-benar berbarengan dengan proses lain.
 *
 * Pemakaian:
 *   php tests/Support/concurrent-worker.php record <waktu_mulai> <qr_id> <kategori_id> <email>
 *   php tests/Support/concurrent-worker.php accept <waktu_mulai> <kunjungan_id> <pic_id>
 */

use App\Exceptions\VisitActionException;
use App\Models\GuestVisit;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\User;
use App\Services\VisitService;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[, $mode, $startAt] = $argv;

while (microtime(true) < (float) $startAt) {
    usleep(500);
}

$visits = app(VisitService::class);

if ($mode === 'record') {
    [, , , $qrId, $categoryId, $email] = $argv;

    $image = imagecreatetruecolor(300, 120);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    imageline($image, 10, 10, 290, 110, imagecolorallocate($image, 0, 0, 0));
    ob_start();
    imagepng($image);
    $png = ob_get_clean();

    $visit = $visits->recordGuestVisit(
        QrCode::findOrFail($qrId),
        PurposeCategory::findOrFail($categoryId),
        [
            'purpose' => 'Uji kiriman bersamaan',
            'vulnerable_group' => 'bukan',
            'name' => 'Tamu Paralel',
            'organization' => 'Pribadi',
            'phone' => '6281234567890',
            'address' => 'Jl. Uji No. 1',
        ],
        $email,
        now(),
        $png,
        '127.0.0.1',
    );

    echo $visit->daily_number;
}

if ($mode === 'accept') {
    [, , , $visitId, $picId] = $argv;

    try {
        $visits->accept(GuestVisit::findOrFail($visitId), User::findOrFail($picId));
        echo 'ok';
    } catch (VisitActionException) {
        echo 'conflict';
    }
}

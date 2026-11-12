<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\GuestVisit;
use App\Models\User;
use App\Models\VisitSignature;
use App\Rules\IndonesianPhone;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ekspor History ke PDF sesuai filter dan cakupan (HIS-4, HIS-5, PRD Bab 12.4).
 *
 * Sejak revisi HRD #13 satu-satunya format adalah PDF, tanpa batas baris: jumlah
 * baris mengikuti filter yang dipilih.
 */
class ExportService
{
    /**
     * Kolom data per kunjungan; PDF menambah kolom Tanda Tangan di akhir.
     */
    public const COLUMNS = [
        'No.', 'Waktu Datang', 'Lokasi', 'Nama Tamu', 'Instansi/Perusahaan', 'Kelompok Rentan',
        'Rincian Keperluan', 'No. Telepon', 'Alamat', 'Email', 'Bidang/UPT Tujuan',
        'Orang yang Ditemui', 'Status', 'Hasil',
    ];

    private const RELATIONS = ['location:id,name', 'assignedDepartment:id,name', 'servedBy:id,name', 'vulnerableGroupOption:code,name'];

    /**
     * Lebar maksimal gambar tanda tangan di PDF.
     */
    private const SIGNATURE_WIDTH = 160;

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Membuat file PDF unduhan dari seluruh baris hasil filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function download(Builder $query, array $filters, ?string $locationName, User $actor): Response
    {
        $rows = (clone $query)->count();

        $this->audit->record('visit.export', null, ['format' => 'pdf', 'filters' => $filters, 'rows' => $rows], $actor);

        $filename = sprintf('sitamu_%s_%s.pdf', $locationName ? Str::slug($locationName) : 'semua', now()->format('Ymd-Hi'));

        return response($this->pdf($query->with(self::RELATIONS), $filters, $locationName, $rows), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Nilai 14 kolom untuk satu kunjungan. Kolom pertama adalah nomor baris di file
     * ekspor, bukan nomor urut kunjungan yang tidak lagi ditampilkan (revisi HRD #5).
     *
     * @return array<int, string>
     */
    public function row(GuestVisit $visit, int $rowNumber): array
    {
        return [
            (string) $rowNumber,
            $visit->check_in_at->format('d/m/Y H:i'),
            $visit->location->name,
            $visit->name,
            $visit->organization,
            $visit->vulnerableGroupLabel(),
            $visit->purpose,
            IndonesianPhone::display($visit->phone),
            $visit->address,
            $visit->email,
            $visit->assignedDepartment?->name ?? '',
            $visit->servedBy?->name ?? '',
            $visit->status->label(),
            $visit->status === VisitStatus::Completed ? (string) $visit->result : '',
        ];
    }

    /**
     * PDF A4 landscape dari view Blade dengan header tabel berulang dan nomor halaman.
     *
     * @param  array<string, mixed>  $filters
     */
    private function pdf(Builder $query, array $filters, ?string $locationName, int $rowCount): string
    {
        // Tanpa batas baris, Dompdf menyusun seluruh tabel di memori; batas PHP dinaikkan
        // khusus untuk request ini agar filter yang lebar tidak terhenti di tengah jalan.
        ini_set('memory_limit', (string) config('sitamu.export.pdf_memory_limit'));
        set_time_limit((int) config('sitamu.export.pdf_time_limit'));

        $visits = $query->get();
        // union() menjaga kunci visit_id; flatMap() mengurutkan ulang kunci sehingga tanda tangan
        // tidak pernah ketemu saat dicari per kunjungan (penyebab revisi HRD #2).
        $signatures = $visits->chunk(100)
            ->reduce(fn (Collection $found, Collection $chunk) => $found->union($this->signatures($chunk)), collect())
            ->map(fn (string $png) => 'data:image/png;base64,'.base64_encode($png));

        $html = view('admin.export.pdf', [
            'rows' => $visits->values()->map(fn (GuestVisit $visit, int $index) => [
                'values' => $this->row($visit, $index + 1),
                'signature' => $signatures->get($visit->id),
            ]),
            'columns' => array_merge(self::COLUMNS, ['Tanda Tangan']),
            'locationName' => $locationName ?? 'Semua lokasi',
            'filters' => $filters,
            'rowCount' => $rowCount,
        ])->render();

        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->getCanvas()->page_text(760, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', null, 7, [0.3, 0.3, 0.3]);

        return $dompdf->output();
    }

    /**
     * Gambar tanda tangan satu batch kunjungan lewat query terpisah tanpa JOIN,
     * diperkecil dengan GD. Kunjungan tanpa gambar tidak ada di hasil.
     *
     * @param  Collection<int, GuestVisit>  $visits
     * @return Collection<int, string>
     */
    private function signatures(Collection $visits): Collection
    {
        return VisitSignature::query()
            ->whereIn('visit_id', $visits->pluck('id'))
            ->get(['visit_id', 'image'])
            ->mapWithKeys(fn (VisitSignature $signature) => [$signature->visit_id => $this->shrink($signature->image)])
            ->filter();
    }

    /**
     * Memperkecil PNG ke lebar maksimal 160 px dengan latar putih.
     */
    private function shrink(string $png): ?string
    {
        $source = @imagecreatefromstring($png);

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::SIGNATURE_WIDTH / $width);
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);

        ob_start();
        imagepng($target);
        $result = (string) ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return $result;
    }
}

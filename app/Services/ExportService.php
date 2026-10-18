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
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor History ke CSV, Excel, dan PDF sesuai filter dan cakupan (HIS-4, HIS-5, PRD Bab 12.4).
 */
class ExportService
{
    /**
     * Kolom yang sama untuk ketiga format; Excel dan PDF menambah kolom Tanda Tangan.
     */
    public const COLUMNS = [
        'No. Urut', 'Waktu Datang', 'Lokasi', 'Nama Tamu', 'Instansi/Perusahaan', 'Kelompok Rentan',
        'Kategori Keperluan', 'Rincian Keperluan', 'No. Telepon', 'Alamat', 'Email', 'Departemen Tujuan',
        'Orang yang Ditemui', 'Status', 'Hasil',
    ];

    private const RELATIONS = ['location:id,name', 'assignedDepartment:id,name', 'servedBy:id,name'];

    private const MIME_TYPES = [
        'csv' => 'text/csv; charset=UTF-8',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf',
    ];

    /**
     * Lebar maksimal gambar tanda tangan di Excel dan PDF.
     */
    private const SIGNATURE_WIDTH = 160;

    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Membuat file unduhan. Batas baris Excel/PDF dicek sebelum file dibuat.
     *
     * @param  array<string, mixed>  $filters
     */
    public function download(Builder $query, string $format, array $filters, ?string $locationName, User $actor): Response
    {
        $rows = (clone $query)->count();
        $limit = config('sitamu.export.rich_max_rows');

        if ($format !== 'csv' && $rows > $limit) {
            throw ValidationException::withMessages([
                'format' => "Hasil filter {$rows} baris, melebihi batas {$limit} baris. Persempit filter atau gunakan ekspor CSV.",
            ]);
        }

        $this->audit->record('visit.export', null, ['format' => $format, 'filters' => $filters, 'rows' => $rows], $actor);

        $filename = sprintf('sitamu_%s_%s.%s', $locationName ? Str::slug($locationName) : 'semua', now()->format('Ymd-Hi'), $format);
        $headers = [
            'Content-Type' => self::MIME_TYPES[$format],
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ];
        $query->with(self::RELATIONS);

        return match ($format) {
            'csv' => $this->csv($query, $filename, $headers),
            'xlsx' => $this->xlsx($query, $filename, $headers),
            'pdf' => $this->pdf($query, $filename, $headers, $filters, $locationName, $rows),
        };
    }

    /**
     * Nilai 15 kolom untuk satu kunjungan.
     *
     * @return array<int, string>
     */
    public function row(GuestVisit $visit): array
    {
        return [
            (string) $visit->daily_number,
            $visit->check_in_at->format('d/m/Y H:i'),
            $visit->location->name,
            $visit->name,
            $visit->organization,
            $visit->vulnerable_group->label(),
            $visit->purpose_category_name,
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
     * CSV UTF-8 dengan BOM, ditulis streaming tanpa batas baris.
     *
     * @param  array<string, string>  $headers
     */
    private function csv(Builder $query, string $filename, array $headers): StreamedResponse
    {
        $delimiter = config('sitamu.export.csv_delimiter');

        return response()->streamDownload(function () use ($query, $delimiter) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, self::COLUMNS, $delimiter);

            foreach ($query->lazy(500) as $visit) {
                fputcsv($output, array_map([$this, 'guardCsvCell'], $this->row($visit)), $delimiter);
            }

            fclose($output);
        }, $filename, $headers);
    }

    /**
     * Excel satu sheet: header tebal dan dibekukan, teks panjang dibungkus, semua nilai
     * ditulis sebagai string agar tidak pernah dievaluasi sebagai rumus.
     *
     * @param  array<string, string>  $headers
     */
    private function xlsx(Builder $query, string $filename, array $headers): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('History');
        $columns = array_merge(self::COLUMNS, ['Tanda Tangan']);

        $sheet->fromArray($columns, null, 'A1');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $rowNumber = 2;

        foreach ($query->get()->chunk(100) as $chunk) {
            $signatures = $this->signatures($chunk);

            foreach ($chunk as $visit) {
                foreach ($this->row($visit) as $index => $value) {
                    $sheet->setCellValueExplicit([$index + 1, $rowNumber], $value, DataType::TYPE_STRING);
                }

                $this->addSignatureImage($sheet, $signatures->get($visit->id), $rowNumber);
                $rowNumber++;
            }
        }

        foreach (['H', 'J', 'O'] as $longTextColumn) {
            $sheet->getStyle("{$longTextColumn}2:{$longTextColumn}{$rowNumber}")->getAlignment()->setWrapText(true);
            $sheet->getColumnDimension($longTextColumn)->setWidth(40);
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'I', 'K', 'L', 'M', 'N'] as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getColumnDimension('P')->setWidth(25);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, $headers);
    }

    /**
     * PDF A4 landscape dari view Blade dengan header tabel berulang dan nomor halaman.
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $filters
     */
    private function pdf(Builder $query, string $filename, array $headers, array $filters, ?string $locationName, int $rowCount): Response
    {
        $visits = $query->get();
        $signatures = $visits->chunk(100)->flatMap(fn (Collection $chunk) => $this->signatures($chunk)->map(
            fn (string $png) => 'data:image/png;base64,'.base64_encode($png),
        ));

        $html = view('admin.export.pdf', [
            'rows' => $visits->map(fn (GuestVisit $visit) => ['values' => $this->row($visit), 'signature' => $signatures->get($visit->id)]),
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

        return response($dompdf->output(), 200, $headers + ['Content-Disposition' => 'attachment; filename="'.$filename.'"']);
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

    /**
     * Menyisipkan gambar tanda tangan di kolom P baris tertentu.
     */
    private function addSignatureImage(Worksheet $sheet, ?string $png, int $rowNumber): void
    {
        if ($png === null) {
            return;
        }

        $drawing = MemoryDrawing::fromString($png);
        $drawing->setCoordinates("P{$rowNumber}");
        $drawing->setOffsetX(4);
        $drawing->setOffsetY(4);
        $drawing->setWorksheet($sheet);

        $sheet->getRowDimension($rowNumber)->setRowHeight(max(30, $drawing->getHeight() * 0.75 + 8));
    }

    /**
     * Mencegah CSV injection: sel diawali =, +, -, @, tab, atau carriage return diberi awalan '.
     */
    private function guardCsvCell(string $value): string
    {
        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}

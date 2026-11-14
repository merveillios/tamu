<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\VisitStatusLog;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit Log PIC untuk Admin dan Super Admin (AUD-4 sampai AUD-6, revisi HRD #8 dan #9).
 *
 * Sumbernya visit_status_logs yang dibuat akun PIC; tidak ada tabel baru. Satu baris =
 * satu aksi PIC dengan tujuh kolom: No, Waktu, PIC, Tamu, Instansi, Rincian Keperluan, Hasil. Karena satu akun
 * PIC dipakai bersama per Bidang/UPT, kolom PIC berisi orang yang ditemui yang dipilih
 * resepsionis saat menugaskan (salinan di log saat aksi), bukan nama akun. Hasil hanya
 * terisi untuk aksi menyimpan hasil (status menjadi Selesai), selain itu "–".
 */
class PicAuditService
{
    /**
     * Judul kolom, urut seperti di halaman dan PDF.
     */
    public const COLUMNS = ['No', 'Waktu', 'PIC', 'Tamu', 'Instansi', 'Rincian Keperluan', 'Hasil'];

    /**
     * Lebar kolom PDF A4 landscape (persen); Hasil paling lebar.
     */
    public const COLUMN_WIDTHS = [4, 12, 12, 13, 13, 21, 25];

    /**
     * Awalan catatan log Selesai yang menyimpan hasil sebelum kunjungan diselesaikan ulang.
     */
    private const PREVIOUS_RESULT_PREFIX = 'Hasil sebelumnya: ';

    public function __construct(
        private readonly HistoryService $history,
        private readonly AuditService $audit,
    ) {
    }

    /**
     * Aksi akun PIC sesuai filter, terbaru di atas.
     *
     * @param  array<string, mixed>  $filters
     */
    public function query(array $filters): Builder
    {
        [$from, $to] = $this->history->dateRange($filters);
        $search = trim((string) ($filters['q'] ?? ''));

        return VisitStatusLog::query()
            ->whereHas('changedBy', fn (Builder $query) => $query
                ->where('role', UserRole::Pic)
                // array_key_exists, bukan nilai: akun PIC tanpa Bidang/UPT dipaksa ke 0 dan harus tetap tersaring.
                ->when(array_key_exists('department_id', $filters), fn (Builder $inner) => $inner->where('department_id', (int) $filters['department_id'])))
            ->when($filters['staff_member_id'] ?? null, fn (Builder $query, $id) => $query->where(fn (Builder $inner) => $inner
                ->where('staff_member_id', $id)
                // Log lama tanpa salinan petugas memakai orang yang ditemui pada kunjungan.
                ->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('staff_member_id')
                    ->whereHas('visit', fn (Builder $visit) => $visit->where('served_by_staff_id', $id)))))
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<=', $to))
            ->when($search !== '', fn (Builder $query) => $query->whereHas('visit', fn (Builder $visit) => $visit
                ->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('organization', 'like', "%{$search}%"))))
            ->with(['staffMember:id,name', 'visit:id,name,organization,purpose,result,served_by_staff_id', 'visit.servedBy:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Tujuh nilai kolom per log, dengan nomor baris mulai dari $firstNumber. Rincian keperluan
     * adalah teks yang ditulis tamu di formulir.
     *
     * @param  Collection<int, VisitStatusLog>  $logs
     * @return Collection<int, array{values: array<int, string>, visit_id: int}>
     */
    public function rows(Collection $logs, int $firstNumber = 1): Collection
    {
        $results = $this->resultsFor($logs);

        return $logs->values()->map(fn (VisitStatusLog $log, int $index) => [
            'visit_id' => $log->visit_id,
            'values' => [
                (string) ($firstNumber + $index),
                $log->created_at->format('d/m/Y H:i'),
                $log->staffMember?->name ?? $log->visit?->servedBy?->name ?? '–',
                $log->visit?->name ?? '–',
                $log->visit?->organization ?? '–',
                $log->visit?->purpose ?? '–',
                $results->get($log->id) ?? '–',
            ],
        ]);
    }

    /**
     * Unduhan PDF satu tabel landscape, urut dari aksi paling awal; tercatat di audit log.
     *
     * @param  array<string, mixed>  $filters
     */
    public function download(array $filters, User $actor): Response
    {
        ini_set('memory_limit', (string) config('sitamu.export.pdf_memory_limit'));
        set_time_limit((int) config('sitamu.export.pdf_time_limit'));

        $logs = $this->query($filters)->reorder()->orderBy('created_at')->orderBy('id')->get();

        $this->audit->record('pic_audit.export', null, ['format' => 'pdf', 'filters' => $filters, 'rows' => $logs->count()], $actor);

        $html = view('admin.export.pic-audit', [
            'columns' => self::COLUMNS,
            'widths' => self::COLUMN_WIDTHS,
            'rows' => $this->rows($logs),
            'filters' => $this->filterSummary($filters),
            'rowCount' => $logs->count(),
        ])->render();

        $options = new Options();
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $canvas->page_text(28, 570, 'Audit Log PIC · '.config('sitamu.institution_name').' · Dicetak '.now()->format('d/m/Y H:i'), null, 7, [0.35, 0.35, 0.35]);
        $canvas->page_text(745, 570, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', null, 7, [0.35, 0.35, 0.35]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="audit-log-pic_'.now()->format('Ymd-Hi').'.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Filter aktif dalam kalimat untuk kepala PDF.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, string>
     */
    public function filterSummary(array $filters): array
    {
        $summary = [];

        if (! empty($filters['staff_member_id'])) {
            $summary[] = 'PIC: '.StaffMember::query()->whereKey($filters['staff_member_id'])->value('name');
        }

        if (! empty($filters['department_id'])) {
            $summary[] = 'Bidang/UPT: '.Department::query()->whereKey($filters['department_id'])->value('name');
        }

        if (! empty($filters['period'])) {
            $summary[] = 'Periode: '.(HistoryService::PERIODS[$filters['period']] ?? $filters['period']);
        } elseif (! empty($filters['from']) || ! empty($filters['to'])) {
            [$from, $to] = $this->history->dateRange($filters);
            $summary[] = 'Tanggal: '.($from?->format('d/m/Y') ?? '…').' – '.($to?->format('d/m/Y') ?? '…');
        }

        if (! empty($filters['q'])) {
            $summary[] = 'Pencarian: "'.Str::limit((string) $filters['q'], 50).'"';
        }

        return $summary;
    }

    /**
     * Hasil per log Selesai. Bila kunjungan pernah dibuka kembali lalu diselesaikan ulang
     * dengan hasil berbeda, log Selesai berikutnya menyimpan hasil lama di catatannya,
     * sehingga log yang lebih awal tetap menampilkan hasil saat itu, bukan hasil terbaru.
     *
     * @param  Collection<int, VisitStatusLog>  $logs
     * @return Collection<int, string>
     */
    private function resultsFor(Collection $logs): Collection
    {
        $completions = $logs->filter(fn (VisitStatusLog $log) => $log->to_status === VisitStatus::Completed);

        if ($completions->isEmpty()) {
            return collect();
        }

        $laterByVisit = VisitStatusLog::query()
            ->whereIn('visit_id', $completions->pluck('visit_id')->unique())
            ->where('to_status', VisitStatus::Completed)
            ->orderBy('id')
            ->get(['id', 'visit_id', 'note'])
            ->groupBy('visit_id');

        return $completions->mapWithKeys(function (VisitStatusLog $log) use ($laterByVisit) {
            $next = $laterByVisit->get($log->visit_id, collect())->first(fn (VisitStatusLog $other) => $other->id > $log->id);
            $result = $next !== null && str_starts_with((string) $next->note, self::PREVIOUS_RESULT_PREFIX)
                ? substr((string) $next->note, strlen(self::PREVIOUS_RESULT_PREFIX))
                : $log->visit?->result;

            return [$log->id => filled($result) ? (string) $result : '–'];
        });
    }
}

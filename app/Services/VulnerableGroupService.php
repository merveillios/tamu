<?php

namespace App\Services;

use App\Models\GuestVisit;
use App\Models\User;
use App\Models\VulnerableGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Aturan bisnis master kelompok rentan (VUL-1 sampai VUL-3).
 *
 * Kelompok yang sudah dipakai kunjungan tidak bisa dihapus, hanya dinonaktifkan, agar
 * riwayat dan ekspor tetap menampilkan namanya. Minimal satu kelompok harus tetap aktif
 * karena kelompok rentan wajib dipilih di formulir tamu.
 */
class VulnerableGroupService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Menambah kelompok rentan; kode dibuat otomatis dari nama dan tidak berubah lagi.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): VulnerableGroup
    {
        $group = VulnerableGroup::create([
            'code' => $this->uniqueCode($data['name']),
            'name' => $data['name'],
            'priority' => $data['priority'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->audit->record('vulnerable_group.create', $group, ['code' => $group->code, 'name' => $group->name], $actor);

        return $group;
    }

    /**
     * Mengubah nama, prioritas antrean, dan urutan tampil. Kode tetap.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(VulnerableGroup $group, array $data, User $actor): VulnerableGroup
    {
        $group->fill([
            'name' => $data['name'],
            'priority' => $data['priority'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
        $changedFields = array_keys($group->getDirty());
        $group->save();

        if ($changedFields !== []) {
            $this->audit->record('vulnerable_group.update', $group, ['fields' => $changedFields], $actor);
        }

        return $group;
    }

    /**
     * Mengaktifkan atau menonaktifkan kelompok; kelompok nonaktif tidak muncul di formulir tamu baru.
     */
    public function setActive(VulnerableGroup $group, bool $active, User $actor): void
    {
        if ($group->is_active === $active) {
            return;
        }

        DB::transaction(function () use ($group, $active, $actor) {
            if (! $active) {
                $this->ensureAnotherActive($group, 'dinonaktifkan');
            }

            $group->forceFill(['is_active' => $active])->save();

            $this->audit->record($active ? 'vulnerable_group.activate' : 'vulnerable_group.deactivate', $group, [], $actor);
        });
    }

    /**
     * Menghapus kelompok yang belum pernah dipakai kunjungan (VUL-3). Server tetap menolak
     * walau request dikirim manual.
     */
    public function delete(VulnerableGroup $group, User $actor): void
    {
        DB::transaction(function () use ($group, $actor) {
            if ($this->isUsed($group)) {
                throw ValidationException::withMessages([
                    'vulnerable_group' => "Kelompok \"{$group->name}\" sudah dipakai kunjungan, jadi hanya bisa dinonaktifkan.",
                ]);
            }

            if ($group->is_active) {
                $this->ensureAnotherActive($group, 'dihapus');
            }

            $this->audit->record('vulnerable_group.delete', $group, ['code' => $group->code, 'name' => $group->name], $actor);
            $group->delete();
        });
    }

    /**
     * Apakah sudah ada kunjungan yang memilih kelompok ini.
     */
    public function isUsed(VulnerableGroup $group): bool
    {
        return GuestVisit::query()->where('vulnerable_group', $group->code)->exists();
    }

    /**
     * Menolak perubahan yang membuat formulir tamu tidak punya pilihan kelompok rentan.
     */
    private function ensureAnotherActive(VulnerableGroup $group, string $action): void
    {
        $othersActive = VulnerableGroup::query()
            ->where('is_active', true)
            ->whereKeyNot($group->id)
            ->lockForUpdate()
            ->exists();

        if (! $othersActive) {
            throw ValidationException::withMessages([
                'vulnerable_group' => "Kelompok \"{$group->name}\" tidak bisa {$action} karena formulir tamu butuh minimal satu kelompok aktif.",
            ]);
        }
    }

    /**
     * Kode dari nama (huruf kecil, garis bawah, maks. 30 karakter), diberi akhiran angka bila sudah ada.
     */
    private function uniqueCode(string $name): string
    {
        $base = Str::limit(Str::slug($name, '_'), 26, '') ?: 'kelompok';
        $base = rtrim($base, '_');
        $code = $base;
        $suffix = 2;

        while (VulnerableGroup::query()->where('code', $code)->exists()) {
            $code = "{$base}_{$suffix}";
            $suffix++;
        }

        return $code;
    }
}

<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\GuestVisit;
use App\Models\Location;
use App\Models\PurposeCategory;
use App\Models\QrCode;
use App\Models\StaffMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LogicException;

/**
 * Satu-satunya sumber cakupan data per peran staf (PRD Bab 12.2, A-6).
 *
 * Super Admin tanpa batas, Admin dibatasi lokasi yang ditugaskan, Resepsionis
 * dibatasi QR miliknya, dan PIC dibatasi departemennya.
 */
class AccessScope
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Request $request,
    ) {
    }

    /**
     * Daftar ID lokasi dalam cakupan akun; null berarti tanpa batas.
     *
     * @return array<int, int>|null
     */
    public function locationIds(User $user): ?array
    {
        if ($user->role === UserRole::SuperAdmin) {
            return null;
        }

        return $this->remember("location_ids.{$user->id}", fn () => match ($user->role) {
            UserRole::Admin => $user->locations()->pluck('locations.id')->all(),
            UserRole::Receptionist => QrCode::query()->where('receptionist_id', $user->id)
                ->distinct()->pluck('location_id')->all(),
            UserRole::Pic => Department::query()->whereKey($user->department_id)->pluck('location_id')->all(),
        });
    }

    /**
     * Menerapkan cakupan akun ke query model yang memakai LimitedByAccess.
     */
    public function apply(Builder $query, User $user): Builder
    {
        return match ($query->getModel()::class) {
            GuestVisit::class => $this->applyToVisits($query, $user),
            QrCode::class => $this->applyToMaster($query, $user, 'location_id'),
            Location::class => $this->applyToMaster($query, $user, 'id'),
            Department::class, PurposeCategory::class, StaffMember::class => $this->applyToMaster($query, $user, null),
            default => throw new LogicException('Model tidak dibatasi AccessScope: '.$query->getModel()::class),
        };
    }

    /**
     * Cakupan kunjungan: lokasi (Admin), QR (Resepsionis), atau departemen (PIC).
     */
    public function applyToVisits(Builder $query, User $user): Builder
    {
        $table = $query->getModel()->getTable();

        return match ($user->role) {
            UserRole::SuperAdmin => $query,
            UserRole::Admin => $query->whereIn("{$table}.location_id", $this->locationIds($user)),
            // QR dengan status apa pun agar kunjungan aktif dari QR nonaktif tetap bisa diselesaikan.
            UserRole::Receptionist => $query->whereIn("{$table}.qr_code_id", $this->qrIds($user)),
            UserRole::Pic => $user->department_id === null
                ? $query->whereIn("{$table}.id", [])
                : $query->where("{$table}.assigned_department_id", $user->department_id),
        };
    }

    /**
     * Cakupan master data. Admin mengelola departemen, kategori, dan petugas di
     * semua lokasi, tetapi QR dan lokasi hanya di lokasi yang ditugaskan.
     */
    public function applyToMaster(Builder $query, User $user, ?string $locationColumn): Builder
    {
        $table = $query->getModel()->getTable();

        return match (true) {
            $user->role === UserRole::SuperAdmin => $query,
            $user->role === UserRole::Admin && $locationColumn === null => $query,
            $user->role === UserRole::Admin => $query->whereIn("{$table}.{$locationColumn}", $this->locationIds($user)),
            default => $query->whereIn("{$table}.{$query->getModel()->getKeyName()}", []),
        };
    }

    /**
     * Mencari data berdasarkan ID di dalam cakupan akun. Di luar cakupan atau
     * tidak ada selalu berakhir 404 yang seragam; percobaan akses ke data yang
     * benar-benar ada dicatat sebagai access.denied.
     *
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel
     */
    public function findOrDeny(string $modelClass, int|string $id, User $user): Model
    {
        $found = $modelClass::query()->forUser($user)->whereKey($id)->first();

        if ($found !== null) {
            return $found;
        }

        if ($modelClass::query()->whereKey($id)->exists()) {
            $this->audit->record('access.denied', null, ['model' => class_basename($modelClass), 'id' => (int) $id], $user);
        }

        abort(404);
    }

    /**
     * ID QR milik akun resepsionis, apa pun status QR-nya.
     *
     * @return array<int, int>
     */
    private function qrIds(User $user): array
    {
        return $this->remember("qr_ids.{$user->id}", fn () => QrCode::query()
            ->where('receptionist_id', $user->id)
            ->pluck('id')
            ->all());
    }

    /**
     * Menyimpan hasil query cakupan di atribut request agar cukup dihitung
     * sekali per request dan tidak terbawa ke request berikutnya.
     */
    private function remember(string $key, callable $resolver): array
    {
        $key = "access_scope.{$key}";

        if (! $this->request->attributes->has($key)) {
            $this->request->attributes->set($key, $resolver());
        }

        return $this->request->attributes->get($key);
    }
}

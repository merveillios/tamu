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
 * Super Admin dan Admin tanpa batas (ADM-5), Resepsionis dibatasi QR miliknya,
 * dan PIC dibatasi departemennya.
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
        if ($user->hasRole(UserRole::SuperAdmin, UserRole::Admin)) {
            return null;
        }

        return $this->remember("location_ids.{$user->id}", fn () => match ($user->role) {
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
            QrCode::class, Location::class, Department::class, PurposeCategory::class, StaffMember::class => $this->applyToMaster($query, $user),
            default => throw new LogicException('Model tidak dibatasi AccessScope: '.$query->getModel()::class),
        };
    }

    /**
     * Cakupan kunjungan: semua (Super Admin dan Admin), QR (Resepsionis), atau departemen (PIC).
     */
    public function applyToVisits(Builder $query, User $user): Builder
    {
        $table = $query->getModel()->getTable();

        return match ($user->role) {
            UserRole::SuperAdmin, UserRole::Admin => $query,
            // QR dengan status apa pun agar kunjungan aktif dari QR nonaktif tetap bisa diselesaikan.
            UserRole::Receptionist => $query->whereIn("{$table}.qr_code_id", $this->qrIds($user)),
            UserRole::Pic => $user->department_id === null
                ? $query->whereIn("{$table}.id", [])
                : $query->where("{$table}.assigned_department_id", $user->department_id),
        };
    }

    /**
     * Cakupan master data: Super Admin dan Admin mengelola semua lokasi (ADM-5),
     * peran lain tidak memiliki akses master data.
     */
    public function applyToMaster(Builder $query, User $user): Builder
    {
        if ($user->hasRole(UserRole::SuperAdmin, UserRole::Admin)) {
            return $query;
        }

        return $query->whereIn("{$query->getModel()->getTable()}.{$query->getModel()->getKeyName()}", []);
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

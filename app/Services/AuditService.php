<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Pencatat audit log aksi penting (AUD-1, PRD Bab 12.3).
 */
class AuditService
{
    /**
     * Kunci detail yang tidak boleh pernah tersimpan di audit log.
     */
    private const FORBIDDEN_DETAIL_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'id_token',
        'credential',
        'signature',
        'result',
    ];

    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Menyimpan satu baris audit log.
     *
     * @param  array<string, mixed>  $detail
     */
    public function record(string $action, ?Model $entity = null, array $detail = [], ?User $actor = null): AuditLog
    {
        $actor ??= $this->request->user();
        $detail = Arr::except($detail, self::FORBIDDEN_DETAIL_KEYS);

        return AuditLog::create([
            'user_id' => $actor?->getKey(),
            'action' => $action,
            'entity_type' => $entity ? Str::snake(class_basename($entity)) : null,
            'entity_id' => $entity?->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 252),
            'detail' => $detail === [] ? null : $detail,
        ]);
    }
}

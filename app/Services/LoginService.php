<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pemeriksa kredensial staf beserta penguncian akun sementara (AUTH-1).
 */
class LoginService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    /**
     * Mengembalikan akun bila email dan password benar serta akun boleh login.
     * Alasan kegagalan hanya dicatat di audit log, tidak pernah ditampilkan.
     */
    public function attempt(string $email, string $password): ?User
    {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->audit->record('auth.login_failed', null, ['email' => $email, 'reason' => 'unknown_email']);

            return null;
        }

        if ($user->locked_until !== null && $user->locked_until->isFuture()) {
            $this->audit->record('auth.login_failed', $user, ['reason' => 'locked'], $user);

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            $this->registerFailure($user);

            return null;
        }

        if (! $user->is_active) {
            $this->audit->record('auth.login_failed', $user, ['reason' => 'inactive'], $user);

            return null;
        }

        $user->forceFill([
            'failed_logins' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        $this->audit->record('auth.login_success', $user, [], $user);

        return $user;
    }

    /**
     * Menambah hitungan gagal dan mengunci akun saat mencapai batas.
     */
    private function registerFailure(User $user): void
    {
        $failures = $user->failed_logins + 1;

        if ($failures < config('sitamu.login.max_attempts')) {
            $user->forceFill(['failed_logins' => $failures])->save();
            $this->audit->record('auth.login_failed', $user, ['reason' => 'wrong_password'], $user);

            return;
        }

        // Hitungan direset agar setelah masa kunci berakhir akun mendapat jatah percobaan baru.
        $user->forceFill([
            'failed_logins' => 0,
            'locked_until' => now()->addMinutes(config('sitamu.login.lock_minutes')),
        ])->save();

        $this->audit->record('auth.locked', $user, ['minutes' => config('sitamu.login.lock_minutes')], $user);
    }
}

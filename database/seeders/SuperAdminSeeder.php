<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Super Admin pertama saat deploy, dari isian .env (SUPERADMIN_NAME, SUPERADMIN_EMAIL,
 * SUPERADMIN_PASSWORD). Tetap tidak ada password bawaan di kode (PRD Bab 5.4).
 *
 * Aman dijalankan berulang: bila sudah ada Super Admin, seeder dilewati dan tidak pernah
 * menimpa akun atau password. Bila isian kosong atau tidak valid, seeder hanya memberi
 * peringatan agar deploy tidak gagal; Super Admin dapat dibuat lewat sitamu:create-superadmin.
 */
class SuperAdminSeeder extends Seeder
{
    /**
     * Password yang ditolak walau panjangnya cukup.
     */
    private const WEAK_PASSWORDS = ['password', 'password123', '12345678', '123456789', 'superadmin', 'sitamu123'];

    public function run(): void
    {
        if (User::query()->where('role', UserRole::SuperAdmin)->exists()) {
            $this->command?->info('Super Admin sudah ada; SuperAdminSeeder dilewati.');

            return;
        }

        $config = config('sitamu.initial_super_admin');
        $data = [
            'name' => trim((string) ($config['name'] ?? '')) ?: 'Super Admin',
            'email' => Str::lower(trim((string) ($config['email'] ?? ''))),
            'password' => (string) ($config['password'] ?? ''),
        ];

        if ($data['email'] === '' || $data['password'] === '') {
            if (app()->environment('local')) {
                $this->command?->info('SUPERADMIN_EMAIL/SUPERADMIN_PASSWORD kosong; di lokal Super Admin dibuat oleh data contoh.');

                return;
            }

            $this->command?->warn('Super Admin belum dibuat: isi SUPERADMIN_EMAIL dan SUPERADMIN_PASSWORD di .env, '
                .'lalu jalankan php artisan db:seed --force. Atau jalankan php artisan sitamu:create-superadmin.');

            return;
        }

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'max:150', 'email:rfc', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'not_in:'.implode(',', self::WEAK_PASSWORDS)],
        ], [
            'name.*' => 'SUPERADMIN_NAME harus 2–100 karakter.',
            'email.unique' => 'SUPERADMIN_EMAIL sudah dipakai akun lain.',
            'email.*' => 'SUPERADMIN_EMAIL tidak valid.',
            'password.*' => 'SUPERADMIN_PASSWORD minimal 12 karakter dan bukan password umum.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->command?->error('Super Admin belum dibuat: '.$message);
            }

            return;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->command?->info("Super Admin {$data['email']} dibuat. Hapus SUPERADMIN_PASSWORD dari .env, lalu jalankan php artisan config:cache.");
    }
}

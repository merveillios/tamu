<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Membuat akun Super Admin secara interaktif. Tidak ada password default (PRD Bab 5.4).
 */
class CreateSuperAdmin extends Command
{
    protected $signature = 'sitamu:create-superadmin';

    protected $description = 'Membuat akun Super Admin SiTamu';

    /**
     * Meminta nama, email, dan password, lalu menyimpan akun Super Admin.
     */
    public function handle(): int
    {
        $data = [
            'name' => trim((string) $this->ask('Nama akun')),
            'email' => Str::lower(trim((string) $this->ask('Email'))),
            'password' => (string) $this->secret('Password (minimal 8 karakter)'),
            'password_confirmation' => (string) $this->secret('Ulangi password'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'string', 'max:150', 'email:rfc', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.*' => 'Nama akun wajib diisi (2–100 karakter).',
            'email.unique' => 'Email sudah digunakan.',
            'email.*' => 'Email tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.*' => 'Password minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $this->info("Super Admin {$data['email']} berhasil dibuat.");

        return self::SUCCESS;
    }
}

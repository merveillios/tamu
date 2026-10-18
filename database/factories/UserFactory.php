<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Pembuat data akun staf untuk tes otomatis.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Nilai bawaan akun staf aktif berperan Admin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password-tes',
            'role' => UserRole::Admin,
            'department_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Mengatur peran akun.
     */
    public function role(UserRole $role): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    /**
     * Membuat akun nonaktif.
     */
    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}

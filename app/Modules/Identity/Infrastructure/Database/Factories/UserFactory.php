<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Database\Factories;

use App\Modules\Identity\Domain\Enums\UserRole;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // 01 + 7 + 8 digits = a valid normalized Bangladesh mobile number
            'phone' => '01'.fake()->unique()->numerify('7########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Customer,
            'is_blocked' => false,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Customer]);
    }

    public function staff(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Staff, 'phone' => null]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Admin, 'phone' => null]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::SuperAdmin, 'phone' => null]);
    }

    public function blocked(): static
    {
        return $this->state(fn (): array => ['is_blocked' => true]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }
}

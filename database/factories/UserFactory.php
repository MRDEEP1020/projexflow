<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),  // make sure it's called as a method            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => Str::random(10),
            'role' => 'user',
            'active_mode' => 'client',
            'is_marketplace_enabled' => false,
            'suspended_at' => null,
            'avatar' => null,  // Changed from avatar_url to avatar
            'slug' => Str::slug($this->faker->userName()),
            'stripe_connect_id' => null,
            'timezone' => 'UTC',
        ];
    }

    // State modifiers
    public function admin(): static
    {
        return $this->state(fn() => ['role' => 'admin']);
    }

    public function freelancer(): static
    {
        return $this->state(fn() => [
            'active_mode' => 'freelancer',
            'is_marketplace_enabled' => true,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn() => ['suspended_at' => now()]);
    }

    public function unverified(): static
    {
        return $this->state(fn() => ['email_verified_at' => null]);
    }
}

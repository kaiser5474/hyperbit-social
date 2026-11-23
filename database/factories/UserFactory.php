<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'facebook_id' => fake()->unique()->numerify('##########'),
            'facebook_token' => fake()->sha256(),
            'facebook_token_expires_at' => now()->addDays(60),
            'avatar' => fake()->imageUrl(200, 200),
            'timezone' => 'America/New_York',
            'locale' => 'es',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutFacebook(): static
    {
        return $this->state(fn (array $attributes) => [
            'facebook_id' => null,
            'facebook_token' => null,
            'facebook_token_expires_at' => null,
        ]);
    }

    public function expiredToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'facebook_token_expires_at' => now()->subDay(),
        ]);
    }
}

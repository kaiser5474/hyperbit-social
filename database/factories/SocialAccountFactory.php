<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialAccountFactory extends Factory
{
    protected $model = SocialAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'platform' => $this->faker->randomElement([
                SocialAccount::PLATFORM_FACEBOOK_PAGE,
                SocialAccount::PLATFORM_INSTAGRAM_BUSINESS,
            ]),
            'platform_id' => $this->faker->unique()->numerify('##########'),
            'platform_name' => $this->faker->company(),
            'platform_username' => $this->faker->userName(),
            'avatar_url' => $this->faker->imageUrl(100, 100),
            'access_token' => $this->faker->sha256(),
            'is_active' => true,
        ];
    }

    public function facebookPage(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => SocialAccount::PLATFORM_FACEBOOK_PAGE,
        ]);
    }

    public function instagram(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => SocialAccount::PLATFORM_INSTAGRAM_BUSINESS,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

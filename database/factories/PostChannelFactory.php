<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostChannelFactory extends Factory
{
    protected $model = PostChannel::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'social_account_id' => SocialAccount::factory(),
            'status' => PostChannel::STATUS_PENDING,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostChannel::STATUS_PUBLISHED,
            'platform_post_id' => $this->faker->numerify('##########_##########'),
            'permalink' => $this->faker->url(),
            'published_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostChannel::STATUS_FAILED,
            'error_message' => 'Test error message',
            'error_code' => '400',
        ]);
    }
}

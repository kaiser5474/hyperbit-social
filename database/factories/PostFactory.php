<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->paragraphs(2, true),
            'media_path' => null,
            'media_type' => null,
            'media_url' => null,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
            'timezone' => 'America/New_York',
            'status' => Post::STATUS_SCHEDULED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_DRAFT,
            'scheduled_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    public function dueNow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Post::STATUS_FAILED,
            'retry_count' => 1,
        ]);
    }

    public function withMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_path' => 'posts/test-image.jpg',
            'media_type' => 'image/jpeg',
            'media_url' => 'https://example.com/storage/posts/test-image.jpg',
        ]);
    }
}

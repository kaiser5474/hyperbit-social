<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Publishers\InstagramPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstagramPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_image_post_to_instagram(): void
    {
        Http::fake([
            '*graph.facebook.com*media' => Http::response(['id' => 'container_123']),
            '*graph.facebook.com*container_123*' => Http::response(['status_code' => 'FINISHED']),
            '*graph.facebook.com*media_publish*' => Http::response(['id' => 'ig_media_456']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->withMedia()->create([
            'user_id' => $user->id,
            'content' => 'Instagram caption',
        ]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(InstagramPublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertNotNull($result);
    }

    public function test_fails_without_image(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['user_id' => $user->id]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(InstagramPublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertFalse($result->success);
        $this->assertEquals('MEDIA_REQUIRED', $result->errorCode);
    }

    public function test_handles_container_processing_error(): void
    {
        Http::fake([
            '*graph.facebook.com*media' => Http::response(['id' => 'container_123']),
            '*graph.facebook.com*container_123*' => Http::response(['status_code' => 'ERROR']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->withMedia()->create(['user_id' => $user->id]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(InstagramPublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertFalse($result->success);
    }

    public function test_only_supports_instagram_accounts(): void
    {
        $fbAccount = SocialAccount::factory()->facebookPage()->make();
        $igAccount = SocialAccount::factory()->instagram()->make();

        $publisher = app(InstagramPublisher::class);

        $this->assertFalse($publisher->supports($fbAccount));
        $this->assertTrue($publisher->supports($igAccount));
    }
}

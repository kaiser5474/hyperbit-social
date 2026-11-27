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

    public function test_publishes_image_post_to_instagram_with_permalink(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && str_contains($url, '/media_publish')) {
                return Http::response(['id' => 'ig_media_456']);
            }

            if ($request->method() === 'POST' && str_contains($url, '/media')) {
                return Http::response(['id' => 'container_123']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'ig_media_456')) {
                return Http::response(['permalink' => 'https://www.instagram.com/p/SHORTCODE123/']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'container_123')) {
                return Http::response(['status_code' => 'FINISHED']);
            }

            return Http::response(['error' => ['message' => 'Unexpected request', 'code' => 1]], 500);
        });

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

        $this->assertTrue($result->success);
        $this->assertSame('ig_media_456', $result->platformPostId);
        $this->assertSame('https://www.instagram.com/p/SHORTCODE123/', $result->permalink);
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
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && str_contains($url, '/media')) {
                return Http::response(['id' => 'container_123']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'container_123')) {
                return Http::response(['status_code' => 'ERROR']);
            }

            return Http::response(['error' => ['message' => 'Unexpected request', 'code' => 1]], 500);
        });

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

    public function test_resumes_existing_container_without_recreating(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && str_contains($url, '/media_publish')) {
                return Http::response(['id' => 'ig_media_789']);
            }

            if ($request->method() === 'POST' && str_contains($url, '/media')) {
                return Http::response(['error' => ['message' => 'Should not recreate', 'code' => 1]], 500);
            }

            if ($request->method() === 'GET' && str_contains($url, 'ig_media_789')) {
                return Http::response(['permalink' => 'https://www.instagram.com/p/RESUME123/']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'existing_container')) {
                return Http::response(['status_code' => 'FINISHED']);
            }

            return Http::response(['error' => ['message' => 'Unexpected request', 'code' => 1]], 500);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->withMedia()->create(['user_id' => $user->id]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'status' => PostChannel::STATUS_PUBLISHING,
            'container_id' => 'existing_container',
            'container_status' => 'IN_PROGRESS',
        ]);

        $publisher = app(InstagramPublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertTrue($result->success);
        $this->assertSame('ig_media_789', $result->platformPostId);
        $this->assertSame('https://www.instagram.com/p/RESUME123/', $result->permalink);
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

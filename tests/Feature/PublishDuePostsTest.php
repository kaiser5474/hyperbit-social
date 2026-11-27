<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublishDuePostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_due_posts_to_facebook(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => '12345_67890']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->dueNow()->create(['user_id' => $user->id]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);

        $postChannel = $post->postChannels->first();
        $this->assertEquals(PostChannel::STATUS_PUBLISHED, $postChannel->status);
        $this->assertEquals('12345_67890', $postChannel->platform_post_id);
    }

    public function test_handles_facebook_api_errors(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth token',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->dueNow()->create(['user_id' => $user->id]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_FAILED, $post->status);

        $postChannel = $post->postChannels->first();
        $this->assertEquals(PostChannel::STATUS_FAILED, $postChannel->status);
        $this->assertNotNull($postChannel->error_message);
    }

    public function test_does_not_process_future_posts(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->scheduled()->create([
            'user_id' => $user->id,
            'scheduled_at' => now()->addHour(),
        ]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_SCHEDULED, $post->status);
    }

    public function test_publishes_to_instagram_with_image(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && str_contains($url, '/media_publish')) {
                return Http::response(['id' => 'ig_post_456']);
            }

            if ($request->method() === 'POST' && str_contains($url, '/media')) {
                return Http::response(['id' => 'container_123']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'ig_post_456')) {
                return Http::response(['permalink' => 'https://www.instagram.com/p/IGCODE456/']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'container_123')) {
                return Http::response(['status_code' => 'FINISHED']);
            }

            return Http::response(['error' => ['message' => 'Unexpected request', 'code' => 1]], 500);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->dueNow()->withMedia()->create(['user_id' => $user->id]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $postChannel = $post->postChannels->first();

        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
        $this->assertEquals(PostChannel::STATUS_PUBLISHED, $postChannel->status);
        $this->assertSame('ig_post_456', $postChannel->platform_post_id);
        $this->assertSame('https://www.instagram.com/p/IGCODE456/', $postChannel->permalink);
    }

    public function test_resumes_stuck_publishing_instagram_post(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if ($request->method() === 'POST' && str_contains($url, '/media_publish')) {
                return Http::response(['id' => 'ig_resumed_999']);
            }

            if ($request->method() === 'POST' && str_contains($url, '/media')) {
                return Http::response(['error' => ['message' => 'Should not recreate container', 'code' => 1]], 500);
            }

            if ($request->method() === 'GET' && str_contains($url, 'ig_resumed_999')) {
                return Http::response(['permalink' => 'https://www.instagram.com/p/RESUMED/']);
            }

            if ($request->method() === 'GET' && str_contains($url, 'stuck_container')) {
                return Http::response(['status_code' => 'FINISHED']);
            }

            return Http::response(['error' => ['message' => 'Unexpected request', 'code' => 1]], 500);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->publishing()->withMedia()->create(['user_id' => $user->id]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'status' => PostChannel::STATUS_PUBLISHING,
            'container_id' => 'stuck_container',
            'container_status' => 'IN_PROGRESS',
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $postChannel = $post->postChannels->first();

        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
        $this->assertEquals(PostChannel::STATUS_PUBLISHED, $postChannel->status);
        $this->assertSame('ig_resumed_999', $postChannel->platform_post_id);
        $this->assertSame('https://www.instagram.com/p/RESUMED/', $postChannel->permalink);
    }

    public function test_does_not_claim_freshly_updated_publishing_post(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->publishing()->withMedia()->create([
            'user_id' => $user->id,
            'updated_at' => now(),
        ]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'status' => PostChannel::STATUS_PUBLISHING,
            'container_id' => 'fresh_container',
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful()
            ->expectsOutput('No posts due for publishing.');

        $post->refresh();
        $this->assertEquals(Post::STATUS_PUBLISHING, $post->status);
    }

    public function test_instagram_fails_without_image(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->dueNow()->create(['user_id' => $user->id]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_FAILED, $post->status);

        $postChannel = $post->postChannels->first();
        $this->assertEquals(PostChannel::STATUS_FAILED, $postChannel->status);
    }

    public function test_due_post_without_channels_is_not_published(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->dueNow()->create(['user_id' => $user->id]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_FAILED, $post->status);
        $this->assertNull($post->published_at);
    }

    public function test_partial_success_marks_post_as_published(): void
    {
        Http::fake([
            'graph.facebook.com/*/feed' => Http::response(['id' => 'fb_post_123']),
            'graph.facebook.com/fb_post_123' => Http::response(['permalink_url' => 'https://facebook.com/post/123']),
        ]);

        $user = User::factory()->create();
        $fbAccount = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $igAccount = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);

        $post = Post::factory()->dueNow()->create(['user_id' => $user->id]);

        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $fbAccount->id,
        ]);

        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $igAccount->id,
        ]);

        $this->artisan('posts:publish-due')
            ->assertSuccessful();

        $post->refresh();
        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
    }
}

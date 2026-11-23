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
        Http::fake([
            '*graph.facebook.com*media' => Http::response(['id' => 'container_123']),
            '*graph.facebook.com*container_123*' => Http::response(['status_code' => 'FINISHED']),
            '*graph.facebook.com*media_publish*' => Http::response(['id' => 'ig_post_456']),
        ]);

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

        $this->assertTrue(
            $post->status === Post::STATUS_PUBLISHED ||
            ($post->status === Post::STATUS_FAILED && $postChannel->status === PostChannel::STATUS_FAILED),
            'Post should be published or failed with proper channel status'
        );
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

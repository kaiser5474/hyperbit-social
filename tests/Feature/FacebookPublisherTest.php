<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Publishers\FacebookPagePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishes_text_post_to_facebook(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => '12345_67890']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['user_id' => $user->id, 'content' => 'Test post content']);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(FacebookPagePublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertTrue($result->success);
        $this->assertEquals('12345_67890', $result->platformPostId);
    }

    public function test_publishes_photo_post_to_facebook(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'photo_123', 'post_id' => '12345_photo_post']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->withMedia()->create([
            'user_id' => $user->id,
            'content' => 'Photo post caption',
        ]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(FacebookPagePublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->platformPostId);
    }

    public function test_handles_api_error_gracefully(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'Invalid OAuth access token',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['user_id' => $user->id]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(FacebookPagePublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertFalse($result->success);
        $this->assertNotNull($result->errorMessage);
    }

    public function test_maps_unavailable_page_permissions_to_reconnect_guidance(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => '(#200) The permission(s) pages_read_engagement,pages_manage_posts are not available.',
                    'type' => 'OAuthException',
                    'code' => 200,
                ],
            ], 403),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->withMedia()->create([
            'user_id' => $user->id,
            'content' => 'Esto es una prueba de Publicacion Programada',
        ]);
        $postChannel = PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $publisher = app(FacebookPagePublisher::class);
        $result = $publisher->publish($postChannel);

        $this->assertFalse($result->success);
        $this->assertSame('200', $result->errorCode);
        $this->assertStringContainsString('pages_manage_posts', $result->errorMessage);
        $this->assertTrue(
            str_contains(strtolower($result->errorMessage), 'reconnect')
            || str_contains(strtolower($result->errorMessage), 'reconecta')
        );
        $this->assertStringNotContainsString('HTTP request returned status code', $result->errorMessage);
        $this->assertStringNotContainsString('HTTP 403', $result->errorMessage);
    }

    public function test_only_supports_facebook_pages(): void
    {
        $fbAccount = SocialAccount::factory()->facebookPage()->make();
        $igAccount = SocialAccount::factory()->instagram()->make();

        $publisher = app(FacebookPagePublisher::class);

        $this->assertTrue($publisher->supports($fbAccount));
        $this->assertFalse($publisher->supports($igAccount));
    }
}

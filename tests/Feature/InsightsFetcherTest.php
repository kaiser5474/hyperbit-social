<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Insights\InsightsFetcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InsightsFetcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetches_facebook_page_insights(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, '/insights')) {
                return Http::response([
                    'data' => [
                        ['name' => 'post_impressions', 'values' => [['value' => 500]]],
                        ['name' => 'post_impressions_unique', 'values' => [['value' => 320]]],
                    ],
                ]);
            }

            return Http::response([
                'reactions' => ['summary' => ['total_count' => 42]],
                'comments' => ['summary' => ['total_count' => 7]],
                'shares' => ['count' => 3],
            ]);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $channel = PostChannel::factory()->published()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'platform_post_id' => '111_222',
        ]);

        app(InsightsFetcher::class)->refreshChannel($channel);

        $channel->refresh();
        $this->assertSame(320, $channel->insights_reach);
        $this->assertSame(500, $channel->insights_views);
        $this->assertSame(42, $channel->insights_likes);
        $this->assertSame(7, $channel->insights_comments);
        $this->assertSame(3, $channel->insights_shares);
        $this->assertNotNull($channel->insights_fetched_at);
        $this->assertNull($channel->insights_error);
    }

    public function test_fetches_instagram_media_insights(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/insights')) {
                return Http::response([
                    'data' => [
                        ['name' => 'reach', 'values' => [['value' => 210]]],
                        ['name' => 'views', 'values' => [['value' => 890]]],
                        ['name' => 'likes', 'values' => [['value' => 55]]],
                        ['name' => 'comments', 'values' => [['value' => 9]]],
                    ],
                ]);
            }

            return Http::response(['like_count' => 55, 'comments_count' => 9]);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->instagram()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->withMedia()->create(['user_id' => $user->id]);
        $channel = PostChannel::factory()->published()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'ig_media_1',
        ]);

        app(InsightsFetcher::class)->refreshChannel($channel);

        $channel->refresh();
        $this->assertSame(210, $channel->insights_reach);
        $this->assertSame(890, $channel->insights_views);
        $this->assertSame(55, $channel->insights_likes);
        $this->assertSame(9, $channel->insights_comments);
        $this->assertNull($channel->insights_error);
    }

    public function test_stores_friendly_error_on_permission_failure(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => '(#200) Requires read_insights permission',
                    'code' => 200,
                    'type' => 'OAuthException',
                ],
            ], 403),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $channel = PostChannel::factory()->published()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'platform_post_id' => '111_222',
        ]);

        app(InsightsFetcher::class)->refreshChannel($channel);

        $channel->refresh();
        $this->assertNotNull($channel->insights_error);
        $this->assertNotNull($channel->insights_fetched_at);
    }

    public function test_skips_fresh_cache_unless_forced(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $channel = PostChannel::factory()->published()->withInsights()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'insights_fetched_at' => now()->subMinutes(10),
        ]);

        app(InsightsFetcher::class)->refreshPost($post, force: false);

        Http::assertNothingSent();
        $this->assertSame(1200, $channel->fresh()->insights_reach);
    }
}

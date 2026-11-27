<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_displays_performance_metrics_without_autofetch(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        PostChannel::factory()->published()->withInsights()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'insights_fetched_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee(__('Performance'));
        $response->assertSee(__('Reach'));
        $response->assertSee('1,200');
        $response->assertSee('3,400');
        Http::assertNothingSent();
    }

    public function test_show_does_not_block_on_missing_insights(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        PostChannel::factory()->published()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'insights_fetched_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('posts.show', $post));

        $response->assertOk();
        $response->assertSee(__('No metrics yet. Click Refresh metrics.'));
        Http::assertNothingSent();
    }

    public function test_refresh_insights_updates_metrics(): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/insights')) {
                return Http::response([
                    'data' => [
                        ['name' => 'post_impressions', 'values' => [['value' => 999]]],
                        ['name' => 'post_impressions_unique', 'values' => [['value' => 444]]],
                    ],
                ]);
            }

            return Http::response([
                'reactions' => ['summary' => ['total_count' => 11]],
                'comments' => ['summary' => ['total_count' => 2]],
                'shares' => ['count' => 1],
            ]);
        });

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['user_id' => $user->id]);
        $channel = PostChannel::factory()->published()->withInsights()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
            'platform_post_id' => 'page_1_post_2',
            'insights_fetched_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)->post(route('posts.insights.refresh', $post));

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('success');

        $channel->refresh();
        $this->assertSame(444, $channel->insights_reach);
        $this->assertSame(999, $channel->insights_views);
        $this->assertSame(11, $channel->insights_likes);
    }

    public function test_guest_cannot_refresh_insights(): void
    {
        $post = Post::factory()->published()->create();

        $this->post(route('posts.insights.refresh', $post))
            ->assertRedirect(route('login'));
    }

    public function test_user_cannot_refresh_another_users_insights(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->post(route('posts.insights.refresh', $post))
            ->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_posts_list(): void
    {
        $user = User::factory()->create();
        Post::factory(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/posts');

        $response->assertStatus(200);
    }

    public function test_user_can_only_see_own_posts(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Post::factory()->create(['user_id' => $user1->id, 'content' => 'User 1 post']);
        Post::factory()->create(['user_id' => $user2->id, 'content' => 'User 2 post']);

        $response = $this->actingAs($user1)->get('/posts');

        $response->assertSee('User 1 post');
        $response->assertDontSee('User 2 post');
    }

    public function test_user_cannot_view_another_users_post(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)->get("/posts/{$post->id}");

        $response->assertForbidden();
    }

    public function test_user_can_create_scheduled_post(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/posts', [
            'content' => 'Test scheduled post',
            'channels' => [$account->id],
            'action' => 'schedule',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'timezone' => 'America/New_York',
        ]);

        $response->assertRedirect(route('posts.index'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Test scheduled post',
            'status' => Post::STATUS_SCHEDULED,
        ]);
    }

    public function test_cannot_create_post_without_channels(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('posts.create'))
            ->post('/posts', [
                'content' => 'Eleanor Roosevelt quote',
                'action' => 'publish_now',
            ]);

        $response->assertRedirect(route('posts.create'));
        $response->assertSessionHasErrors(['channels']);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_cannot_create_post_with_empty_channels(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->from(route('posts.create'))
            ->post('/posts', [
                'content' => 'Eleanor Roosevelt quote',
                'channels' => [],
                'action' => 'publish_now',
            ]);

        $response->assertRedirect(route('posts.create'));
        $response->assertSessionHasErrors(['channels']);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_cannot_create_post_with_another_users_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $foreignAccount = SocialAccount::factory()->facebookPage()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user)->post('/posts', [
            'content' => 'Should not publish',
            'channels' => [$foreignAccount->id],
            'action' => 'publish_now',
        ]);

        $response->assertSessionHasErrors(['channels']);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_cannot_update_post_without_channels(): void
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->scheduled()->create(['user_id' => $user->id]);
        $post->channels()->attach($account->id, ['status' => 'pending']);

        $response = $this->actingAs($user)
            ->from(route('posts.edit', $post))
            ->put("/posts/{$post->id}", [
                'content' => 'Updated without a channel',
                'channels' => [],
            ]);

        $response->assertRedirect(route('posts.edit', $post));
        $response->assertSessionHasErrors(['channels']);
        $this->assertTrue($post->fresh()->channels->contains($account));
    }

    public function test_cannot_publish_now_post_without_channels(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/posts/{$post->id}/publish-now");

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('error');
        $this->assertEquals(Post::STATUS_DRAFT, $post->fresh()->status);
    }

    public function test_create_form_blocks_submit_without_a_channel(): void
    {
        $user = User::factory()->create();
        SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('posts.create'));

        $response->assertOk();
        $response->assertSee('validateChannels', false);
        $response->assertSee(__('Please select at least one channel.'));
    }

    public function test_user_can_cancel_scheduled_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->scheduled()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/posts/{$post->id}/cancel");

        $response->assertRedirect();

        $post->refresh();
        $this->assertEquals(Post::STATUS_CANCELLED, $post->status);
    }

    public function test_user_cannot_cancel_published_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/posts/{$post->id}/cancel");

        $response->assertRedirect();

        $post->refresh();
        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
    }

    public function test_publish_now_flashes_error_when_facebook_permissions_are_unavailable(): void
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
        $post = Post::factory()->scheduled()->create([
            'user_id' => $user->id,
            'content' => 'Esto es una prueba de Publicacion Programada',
        ]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)->post(route('posts.publish-now', $post));

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');

        $post->refresh();
        $this->assertEquals(Post::STATUS_FAILED, $post->status);

        $postChannel = $post->postChannels()->first();
        $this->assertEquals(PostChannel::STATUS_FAILED, $postChannel->status);
        $this->assertStringContainsString('pages_manage_posts', $postChannel->error_message);
    }

    public function test_publish_now_flashes_success_when_facebook_publish_succeeds(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => '12345_67890', 'permalink_url' => 'https://facebook.com/post/123']),
        ]);

        $user = User::factory()->create();
        $account = SocialAccount::factory()->facebookPage()->create(['user_id' => $user->id]);
        $post = Post::factory()->scheduled()->create([
            'user_id' => $user->id,
            'content' => 'Ready to go live',
        ]);
        PostChannel::factory()->create([
            'post_id' => $post->id,
            'social_account_id' => $account->id,
        ]);

        $response = $this->actingAs($user)->post(route('posts.publish-now', $post));

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');

        $post->refresh();
        $this->assertEquals(Post::STATUS_PUBLISHED, $post->status);
    }

    public function test_user_can_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/posts/{$post->id}");

        $response->assertRedirect(route('posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}

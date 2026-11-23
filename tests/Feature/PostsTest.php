<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_can_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/posts/{$post->id}");

        $response->assertRedirect(route('posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}

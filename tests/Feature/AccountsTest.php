<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'facebook_token' => 'test_token',
            'facebook_token_expires_at' => now()->addDays(30),
        ]);
    }

    public function test_connecting_facebook_page_creates_social_account(): void
    {
        Http::fake([
            '*graph.facebook.com*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789',
                        'name' => 'Test Page',
                        'access_token' => 'page_access_token_123',
                        'category' => 'Business',
                        'picture' => ['data' => ['url' => 'https://example.com/pic.jpg']],
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['fb_123456789'],
            ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $this->user->id,
            'platform' => SocialAccount::PLATFORM_FACEBOOK_PAGE,
            'platform_id' => '123456789',
            'platform_name' => 'Test Page',
        ]);
    }

    public function test_connecting_instagram_account_creates_social_account(): void
    {
        Http::fake([
            '*graph.facebook.com*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789',
                        'name' => 'Test Page',
                        'access_token' => 'page_access_token_123',
                        'instagram_business_account' => ['id' => '987654321'],
                    ],
                ],
            ]),
            '*graph.facebook.com*/987654321*' => Http::response([
                'id' => '987654321',
                'username' => 'test_instagram',
                'name' => 'Test Instagram',
                'profile_picture_url' => 'https://example.com/ig_pic.jpg',
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['ig_987654321_123456789'],
            ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $this->user->id,
            'platform' => SocialAccount::PLATFORM_INSTAGRAM_BUSINESS,
            'platform_id' => '987654321',
            'platform_username' => 'test_instagram',
            'parent_page_id' => '123456789',
        ]);
    }

    public function test_connecting_multiple_accounts(): void
    {
        Http::fake([
            '*graph.facebook.com*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => '111',
                        'name' => 'Page One',
                        'access_token' => 'token_111',
                    ],
                    [
                        'id' => '222',
                        'name' => 'Page Two',
                        'access_token' => 'token_222',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['fb_111', 'fb_222'],
            ]);

        $response->assertRedirect(route('accounts.index'));
        $response->assertSessionHas('success');

        $this->assertEquals(2, $this->user->socialAccounts()->count());
    }

    public function test_empty_selection_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => [],
            ]);

        $response->assertSessionHasErrors('accounts');
    }

    public function test_missing_accounts_field_returns_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), []);

        $response->assertSessionHasErrors('accounts');
    }

    public function test_errors_are_visible_on_page(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('accounts.connect'))
            ->post(route('accounts.store'), [
                'accounts' => [],
            ]);

        $response->assertRedirect(route('accounts.connect'));
        $response->assertSessionHasErrors('accounts');
    }

    public function test_expired_token_redirects_to_facebook_auth(): void
    {
        $this->user->update([
            'facebook_token_expires_at' => now()->subDays(1),
        ]);

        Http::fake();

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['fb_123'],
            ]);

        $response->assertRedirect(route('auth.facebook'));
        $response->assertSessionHas('info');
    }

    public function test_invalid_page_id_is_skipped(): void
    {
        Http::fake([
            '*graph.facebook.com*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789',
                        'name' => 'Test Page',
                        'access_token' => 'page_access_token_123',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['fb_nonexistent', 'fb_123456789'],
            ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertEquals(1, $this->user->socialAccounts()->count());
        $this->assertDatabaseHas('social_accounts', [
            'platform_id' => '123456789',
        ]);
    }

    public function test_updating_existing_account_refreshes_token(): void
    {
        $account = SocialAccount::factory()->create([
            'user_id' => $this->user->id,
            'platform' => SocialAccount::PLATFORM_FACEBOOK_PAGE,
            'platform_id' => '123456789',
            'access_token' => 'old_token',
        ]);

        Http::fake([
            '*graph.facebook.com*/me/accounts*' => Http::response([
                'data' => [
                    [
                        'id' => '123456789',
                        'name' => 'Updated Page Name',
                        'access_token' => 'new_token',
                    ],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'accounts' => ['fb_123456789'],
            ]);

        $response->assertRedirect(route('accounts.index'));

        $account->refresh();
        $this->assertEquals('Updated Page Name', $account->platform_name);
    }
}

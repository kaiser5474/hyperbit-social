<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\FacebookController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_guest_can_see_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('HyperBit Social');
    }

    public function test_guest_is_redirected_to_home_from_login(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect(route('home'));
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_facebook_auth_redirects_to_oauth_with_state(): void
    {
        $response = $this->get('/auth/facebook');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('facebook.com', $location);
        $this->assertStringContainsString('state=', $location);

        $this->assertTrue(session()->has(FacebookController::OAUTH_STATE_SESSION_KEY));

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $query);
        $this->assertSame(session(FacebookController::OAUTH_STATE_SESSION_KEY), $query['state'] ?? null);
        $this->assertNotSame(csrf_token(), $query['state'] ?? null);
    }

    public function test_facebook_callback_rejects_invalid_oauth_state(): void
    {
        $this->withSession([
            FacebookController::OAUTH_STATE_SESSION_KEY => 'expected-state-value',
        ]);

        $response = $this->get('/auth/facebook/callback?code=fake-code&state=wrong-state');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
        $this->assertFalse(session()->has(FacebookController::OAUTH_STATE_SESSION_KEY));
    }

    public function test_facebook_callback_rejects_missing_oauth_state(): void
    {
        $response = $this->get('/auth/facebook/callback?code=fake-code&state=some-state');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_facebook_callback_accepts_valid_state_and_logs_in(): void
    {
        Http::fake([
            '*/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-lived-token'])
                ->push(['access_token' => 'long-lived-token', 'expires_in' => 5184000]),
            '*/me*' => Http::response([
                'id' => 'fb-user-123',
                'name' => 'Security Tester',
                'picture' => ['data' => ['url' => 'https://example.com/avatar.jpg']],
            ]),
        ]);

        $state = 'valid-oauth-state-token';

        $response = $this->withSession([
            FacebookController::OAUTH_STATE_SESSION_KEY => $state,
        ])->get('/auth/facebook/callback?code=auth-code&state='.$state);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('facebook_id', 'fb-user-123')->first();
        $this->assertNotNull($user);
        $this->assertSame('long-lived-token', $user->facebook_token);
        $this->assertFalse(session()->has(FacebookController::OAUTH_STATE_SESSION_KEY));
    }

    public function test_facebook_auth_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->get('/auth/facebook')->assertRedirect();
        }

        $this->get('/auth/facebook')->assertStatus(429);
    }
}

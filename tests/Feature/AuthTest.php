<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_facebook_auth_redirects_to_oauth(): void
    {
        $response = $this->get('/auth/facebook');

        $response->assertRedirect();
        $this->assertStringContainsString('facebook.com', $response->headers->get('Location'));
    }
}

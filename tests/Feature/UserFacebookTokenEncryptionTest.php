<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserFacebookTokenEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_token_is_encrypted_at_rest(): void
    {
        $plainToken = 'plain-facebook-user-token';

        $user = User::factory()->create([
            'facebook_token' => $plainToken,
        ]);

        $raw = DB::table('users')->where('id', $user->id)->value('facebook_token');

        $this->assertNotSame($plainToken, $raw);
        $this->assertSame($plainToken, Crypt::decryptString($raw));
        $this->assertSame($plainToken, $user->fresh()->facebook_token);
    }

    public function test_facebook_token_is_hidden_from_array_serialization(): void
    {
        $user = User::factory()->create([
            'facebook_token' => 'secret-token',
        ]);

        $this->assertArrayNotHasKey('facebook_token', $user->toArray());
    }

    public function test_migration_encrypts_existing_plaintext_tokens(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => null,
            'facebook_id' => 'legacy-fb-1',
            'facebook_token' => 'legacy-plain-token',
            'facebook_token_expires_at' => now()->addDays(30),
            'timezone' => 'America/New_York',
            'locale' => 'es',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_03_26_000001_encrypt_existing_user_facebook_tokens.php');
        $migration->up();

        $raw = DB::table('users')->where('id', $userId)->value('facebook_token');

        $this->assertNotSame('legacy-plain-token', $raw);
        $this->assertSame('legacy-plain-token', Crypt::decryptString($raw));
        $this->assertSame('legacy-plain-token', User::find($userId)->facebook_token);
    }
}

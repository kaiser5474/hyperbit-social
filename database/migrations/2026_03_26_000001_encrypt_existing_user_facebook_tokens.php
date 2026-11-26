<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (
            DB::table('users')
                ->whereNotNull('facebook_token')
                ->orderBy('id')
                ->cursor() as $user
        ) {
            $raw = $user->facebook_token;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            try {
                Crypt::decryptString($raw);

                continue;
            } catch (DecryptException) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'facebook_token' => Crypt::encryptString($raw),
                    ]);
            }
        }
    }

    public function down(): void
    {
        foreach (
            DB::table('users')
                ->whereNotNull('facebook_token')
                ->orderBy('id')
                ->cursor() as $user
        ) {
            $raw = $user->facebook_token;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            try {
                $plain = Crypt::decryptString($raw);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'facebook_token' => $plain,
                    ]);
            } catch (DecryptException) {
                // Already plaintext or undecryptable — leave as-is.
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Platform type: facebook_page, instagram_business, threads (future)
            $table->string('platform');

            // Platform-specific IDs
            $table->string('platform_id');
            $table->string('platform_name');
            $table->string('platform_username')->nullable();
            $table->text('avatar_url')->nullable();

            // For Instagram linked to a Facebook Page
            $table->string('parent_page_id')->nullable();

            // Encrypted access token (page-level token for FB, same for IG)
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();

            // Account status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'platform', 'platform_id']);
            $table->index(['platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};

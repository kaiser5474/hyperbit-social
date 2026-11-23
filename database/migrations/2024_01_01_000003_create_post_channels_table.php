<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_account_id')->constrained()->onDelete('cascade');

            // Per-channel status: pending, publishing, published, failed
            $table->string('status')->default('pending');

            // Platform-specific post ID after publishing
            $table->string('platform_post_id')->nullable();
            $table->string('permalink')->nullable();

            // Error tracking
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();

            // For Instagram container workflow
            $table->string('container_id')->nullable();
            $table->string('container_status')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'social_account_id']);
            $table->index(['status', 'post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_channels');
    }
};

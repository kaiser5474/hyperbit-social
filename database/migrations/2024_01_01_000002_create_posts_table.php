<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Post content
            $table->text('content');
            $table->string('media_path')->nullable();
            $table->string('media_type')->nullable();
            $table->string('media_url')->nullable();

            // Scheduling
            $table->timestamp('scheduled_at')->nullable();
            $table->string('timezone')->default('America/New_York');

            // Post status: draft, scheduled, publishing, published, failed, cancelled
            $table->string('status')->default('draft');

            // Publishing metadata
            $table->timestamp('published_at')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

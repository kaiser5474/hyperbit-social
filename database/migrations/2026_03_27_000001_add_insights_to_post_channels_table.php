<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('insights_reach')->nullable()->after('published_at');
            $table->unsignedBigInteger('insights_views')->nullable()->after('insights_reach');
            $table->unsignedBigInteger('insights_likes')->nullable()->after('insights_views');
            $table->unsignedBigInteger('insights_comments')->nullable()->after('insights_likes');
            $table->unsignedBigInteger('insights_shares')->nullable()->after('insights_comments');
            $table->timestamp('insights_fetched_at')->nullable()->after('insights_shares');
            $table->text('insights_error')->nullable()->after('insights_fetched_at');
        });
    }

    public function down(): void
    {
        Schema::table('post_channels', function (Blueprint $table) {
            $table->dropColumn([
                'insights_reach',
                'insights_views',
                'insights_likes',
                'insights_comments',
                'insights_shares',
                'insights_fetched_at',
                'insights_error',
            ]);
        });
    }
};

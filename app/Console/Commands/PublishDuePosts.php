<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\PostChannel;
use App\Services\Publishers\PublisherManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PublishDuePosts extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Publish all scheduled posts that are due';

    public function __construct(
        protected PublisherManager $publisherManager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $posts = Post::dueForPublishing()
            ->with(['postChannels.socialAccount'])
            ->lockForUpdate()
            ->get();

        if ($posts->isEmpty()) {
            $this->info('No posts due for publishing.');

            return self::SUCCESS;
        }

        $this->info("Found {$posts->count()} post(s) due for publishing.");

        foreach ($posts as $post) {
            $this->processPost($post);
        }

        $this->info('Publishing complete.');

        return self::SUCCESS;
    }

    protected function processPost(Post $post): void
    {
        $this->line("Processing post #{$post->id}...");

        if ($post->retry_count >= Post::MAX_RETRIES) {
            $this->warn("Post #{$post->id} has exceeded maximum retries. Skipping.");

            return;
        }

        DB::transaction(function () use ($post) {
            $post->markAsPublishing();

            $allSuccessful = true;
            $hasPartialSuccess = false;

            foreach ($post->postChannels as $postChannel) {
                if ($postChannel->isPublished()) {
                    continue;
                }

                $result = $this->publishChannel($postChannel);

                if ($result) {
                    $hasPartialSuccess = true;
                } else {
                    $allSuccessful = false;
                }
            }

            $this->updatePostStatus($post, $allSuccessful, $hasPartialSuccess);
        });
    }

    protected function publishChannel(PostChannel $postChannel): bool
    {
        $account = $postChannel->socialAccount;
        $this->line("  → Publishing to {$account->platform_name} ({$account->platform})");

        try {
            $result = $this->publisherManager->publish($postChannel);

            if ($result->success) {
                $postChannel->markAsPublished($result->platformPostId, $result->permalink);
                $this->info("    ✓ Published successfully: {$result->platformPostId}");

                Log::info('Channel published successfully', [
                    'post_id' => $postChannel->post_id,
                    'channel_id' => $postChannel->id,
                    'platform_post_id' => $result->platformPostId,
                ]);

                return true;
            }

            if ($result->needsPolling) {
                $postChannel->setContainer($result->containerId, $result->containerStatus);
                $this->warn("    ⏳ Pending (container: {$result->containerId})");

                Log::info('Channel publishing pending', [
                    'post_id' => $postChannel->post_id,
                    'channel_id' => $postChannel->id,
                    'container_id' => $result->containerId,
                ]);

                return false;
            }

            $postChannel->markAsFailed($result->errorMessage, $result->errorCode);
            $this->error("    ✗ Failed: {$result->errorMessage}");

            Log::error('Channel publish failed', [
                'post_id' => $postChannel->post_id,
                'channel_id' => $postChannel->id,
                'error' => $result->errorMessage,
                'code' => $result->errorCode,
            ]);

            return false;

        } catch (\Throwable $e) {
            $postChannel->markAsFailed($e->getMessage());
            $this->error("    ✗ Error: {$e->getMessage()}");

            Log::error('Channel publish exception', [
                'post_id' => $postChannel->post_id,
                'channel_id' => $postChannel->id,
                'exception' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function updatePostStatus(Post $post, bool $allSuccessful, bool $hasPartialSuccess): void
    {
        $post->refresh();

        $pendingCount = $post->postChannels()->whereIn('status', ['pending', 'publishing'])->count();
        $failedCount = $post->postChannels()->where('status', 'failed')->count();
        $totalCount = $post->postChannels()->count();

        if ($allSuccessful && $pendingCount === 0) {
            $post->markAsPublished();
            $this->info("Post #{$post->id} published successfully.");
        } elseif ($pendingCount === 0 && $failedCount === $totalCount) {
            $post->markAsFailed();
            $this->error("Post #{$post->id} failed on all channels.");
        } elseif ($pendingCount === 0 && $hasPartialSuccess) {
            $post->markAsPublished();
            $this->warn("Post #{$post->id} partially published (some channels failed).");
        } elseif ($pendingCount > 0) {
            $this->info("Post #{$post->id} has {$pendingCount} channel(s) still pending.");
        }
    }
}

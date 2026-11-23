<?php

namespace App\Services\Publishers;

use App\Contracts\PublisherInterface;
use App\Contracts\PublishResult;
use App\Models\PostChannel;
use App\Models\SocialAccount;

/**
 * Threads Publisher - Stub for future implementation
 *
 * TODO: Implement Threads API publishing when ready
 * - Threads API uses similar OAuth flow to Instagram
 * - Text posts via /{threads-user-id}/threads
 * - Image posts via /{threads-user-id}/threads with image_url
 * - Container workflow similar to Instagram
 *
 * @see https://developers.facebook.com/docs/threads
 */
class ThreadsPublisher implements PublisherInterface
{
    public function supports(SocialAccount $account): bool
    {
        return $account->platform === SocialAccount::PLATFORM_THREADS;
    }

    public function publish(PostChannel $postChannel): PublishResult
    {
        return PublishResult::failure(
            __('Threads publishing is coming soon!'),
            'NOT_IMPLEMENTED'
        );
    }

    public function getPlatformName(): string
    {
        return 'Threads';
    }
}

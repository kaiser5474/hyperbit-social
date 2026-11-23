<?php

namespace App\Services\Publishers;

use App\Contracts\PublisherInterface;
use App\Contracts\PublishResult;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\Log;

class InstagramPublisher implements PublisherInterface
{
    protected const MAX_POLL_ATTEMPTS = 10;

    protected const POLL_INTERVAL_SECONDS = 3;

    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function supports(SocialAccount $account): bool
    {
        return $account->platform === SocialAccount::PLATFORM_INSTAGRAM_BUSINESS;
    }

    public function publish(PostChannel $postChannel): PublishResult
    {
        $post = $postChannel->post;
        $account = $postChannel->socialAccount;

        if (! $post->hasMedia()) {
            return PublishResult::failure(
                __('Instagram requires an image for feed posts.'),
                'MEDIA_REQUIRED'
            );
        }

        try {
            $postChannel->markAsPublishing();

            $containerId = $postChannel->container_id;

            if (! $containerId) {
                $containerResult = $this->createContainer($account, $post);
                $containerId = $containerResult['id'];
                $postChannel->setContainer($containerId, 'IN_PROGRESS');
            }

            $status = $this->waitForContainerReady($containerId, $account->access_token);

            if ($status !== 'FINISHED') {
                if ($status === 'IN_PROGRESS') {
                    return PublishResult::pending($containerId, $status);
                }

                return PublishResult::failure(
                    __('Instagram media processing failed: :status', ['status' => $status]),
                    'CONTAINER_'.$status
                );
            }

            $publishResult = $this->client->publishInstagramMedia(
                $account->platform_id,
                $account->access_token,
                $containerId
            );

            $platformPostId = $publishResult['id'] ?? null;

            if (! $platformPostId) {
                return PublishResult::failure('No media ID returned from Instagram');
            }

            $permalink = "https://www.instagram.com/p/{$platformPostId}/";

            $account->markAsUsed();

            Log::info('Published to Instagram', [
                'post_id' => $post->id,
                'account_id' => $account->platform_id,
                'platform_post_id' => $platformPostId,
            ]);

            return PublishResult::success($platformPostId, $permalink);

        } catch (MetaApiException $e) {
            Log::error('Instagram publish failed', [
                'post_id' => $post->id,
                'account_id' => $account->platform_id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $account->markError($e->getMessage());

            return PublishResult::failure($e->getUserFriendlyMessage(), (string) $e->getCode());

        } catch (\Exception $e) {
            Log::error('Instagram publish unexpected error', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return PublishResult::failure($e->getMessage());
        }
    }

    public function getPlatformName(): string
    {
        return 'Instagram';
    }

    protected function createContainer(SocialAccount $account, $post): array
    {
        return $this->client->createInstagramMediaContainer(
            $account->platform_id,
            $account->access_token,
            $post->getPublicMediaUrl(),
            $post->content
        );
    }

    protected function waitForContainerReady(string $containerId, string $accessToken): string
    {
        for ($i = 0; $i < self::MAX_POLL_ATTEMPTS; $i++) {
            $status = $this->client->getInstagramContainerStatus($containerId, $accessToken);
            $statusCode = $status['status_code'] ?? 'UNKNOWN';

            if ($statusCode === 'FINISHED') {
                return 'FINISHED';
            }

            if (in_array($statusCode, ['ERROR', 'EXPIRED'])) {
                return $statusCode;
            }

            sleep(self::POLL_INTERVAL_SECONDS);
        }

        return 'IN_PROGRESS';
    }
}

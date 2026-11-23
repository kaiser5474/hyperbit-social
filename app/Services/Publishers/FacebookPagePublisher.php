<?php

namespace App\Services\Publishers;

use App\Contracts\PublisherInterface;
use App\Contracts\PublishResult;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\Log;

class FacebookPagePublisher implements PublisherInterface
{
    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function supports(SocialAccount $account): bool
    {
        return $account->platform === SocialAccount::PLATFORM_FACEBOOK_PAGE;
    }

    public function publish(PostChannel $postChannel): PublishResult
    {
        $post = $postChannel->post;
        $account = $postChannel->socialAccount;

        try {
            $postChannel->markAsPublishing();

            if ($post->hasMedia()) {
                $result = $this->publishWithPhoto($account, $post->content, $post->getPublicMediaUrl());
            } else {
                $result = $this->publishText($account, $post->content);
            }

            $platformPostId = $result['id'] ?? $result['post_id'] ?? null;

            if (! $platformPostId) {
                return PublishResult::failure('No post ID returned from Facebook');
            }

            $permalink = $this->client->getPostPermalink($platformPostId, $account->access_token);

            $account->markAsUsed();

            Log::info('Published to Facebook Page', [
                'post_id' => $post->id,
                'page_id' => $account->platform_id,
                'platform_post_id' => $platformPostId,
            ]);

            return PublishResult::success($platformPostId, $permalink);

        } catch (MetaApiException $e) {
            Log::error('Facebook publish failed', [
                'post_id' => $post->id,
                'page_id' => $account->platform_id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $account->markError($e->getMessage());

            return PublishResult::failure($e->getUserFriendlyMessage(), (string) $e->getCode());

        } catch (\Exception $e) {
            Log::error('Facebook publish unexpected error', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);

            return PublishResult::failure($e->getMessage());
        }
    }

    public function getPlatformName(): string
    {
        return 'Facebook Page';
    }

    protected function publishText(SocialAccount $account, string $message): array
    {
        return $this->client->publishToFacebookFeed(
            $account->platform_id,
            $account->access_token,
            $message
        );
    }

    protected function publishWithPhoto(SocialAccount $account, string $message, string $imageUrl): array
    {
        return $this->client->publishPhotoToFacebook(
            $account->platform_id,
            $account->access_token,
            $imageUrl,
            $message
        );
    }
}

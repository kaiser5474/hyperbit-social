<?php

namespace App\Services\Publishers;

use App\Contracts\PublisherInterface;
use App\Contracts\PublishResult;
use App\Models\PostChannel;
use App\Models\SocialAccount;

class PublisherManager
{
    /** @var array<PublisherInterface> */
    protected array $publishers = [];

    public function __construct(
        FacebookPagePublisher $facebookPublisher,
        InstagramPublisher $instagramPublisher,
        ThreadsPublisher $threadsPublisher,
    ) {
        $this->publishers = [
            $facebookPublisher,
            $instagramPublisher,
            $threadsPublisher,
        ];
    }

    public function getPublisherFor(SocialAccount $account): ?PublisherInterface
    {
        foreach ($this->publishers as $publisher) {
            if ($publisher->supports($account)) {
                return $publisher;
            }
        }

        return null;
    }

    public function publish(PostChannel $postChannel): PublishResult
    {
        $publisher = $this->getPublisherFor($postChannel->socialAccount);

        if (! $publisher) {
            return PublishResult::failure(
                __('No publisher available for this platform.'),
                'NO_PUBLISHER'
            );
        }

        return $publisher->publish($postChannel);
    }

    public function getSupportedPlatforms(): array
    {
        return [
            SocialAccount::PLATFORM_FACEBOOK_PAGE => 'Facebook Page',
            SocialAccount::PLATFORM_INSTAGRAM_BUSINESS => 'Instagram',
            SocialAccount::PLATFORM_THREADS => 'Threads (Coming Soon)',
        ];
    }
}

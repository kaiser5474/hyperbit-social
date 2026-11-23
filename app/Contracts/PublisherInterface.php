<?php

namespace App\Contracts;

use App\Models\PostChannel;
use App\Models\SocialAccount;

interface PublisherInterface
{
    public function supports(SocialAccount $account): bool;

    public function publish(PostChannel $postChannel): PublishResult;

    public function getPlatformName(): string;
}

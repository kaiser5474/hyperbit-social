<?php

namespace App\Contracts;

class PublishResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $platformPostId = null,
        public readonly ?string $permalink = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $containerId = null,
        public readonly ?string $containerStatus = null,
        public readonly bool $needsPolling = false,
    ) {}

    public static function success(string $platformPostId, ?string $permalink = null): self
    {
        return new self(
            success: true,
            platformPostId: $platformPostId,
            permalink: $permalink,
        );
    }

    public static function failure(string $message, ?string $code = null): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            errorCode: $code,
        );
    }

    public static function pending(string $containerId, string $containerStatus): self
    {
        return new self(
            success: false,
            containerId: $containerId,
            containerStatus: $containerStatus,
            needsPolling: true,
        );
    }
}

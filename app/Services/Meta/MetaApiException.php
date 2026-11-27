<?php

namespace App\Services\Meta;

use Exception;

class MetaApiException extends Exception
{
    protected ?int $errorSubcode;

    protected ?string $errorType;

    public function __construct(
        string $message,
        int $code = 0,
        ?int $errorSubcode = null,
        ?string $errorType = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorSubcode = $errorSubcode;
        $this->errorType = $errorType;
    }

    public function getErrorSubcode(): ?int
    {
        return $this->errorSubcode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function isTokenExpired(): bool
    {
        return $this->code === 190 || $this->errorSubcode === 463;
    }

    public function isPermissionError(): bool
    {
        if ($this->isTokenExpired()) {
            return false;
        }

        return $this->code === 10 || ($this->code >= 200 && $this->code <= 299);
    }

    public function isRateLimited(): bool
    {
        return $this->code === 32 || $this->code === 613;
    }

    public function getUserFriendlyMessage(): string
    {
        if ($this->isTokenExpired()) {
            return __('Your Facebook session has expired. Please reconnect your account.');
        }

        if ($this->isPermissionError()) {
            if ($this->permissionsAreUnavailable()) {
                return __('Facebook Page posting permissions are not available on this Meta app. In Meta for Developers, add pages_manage_posts and pages_read_engagement to the Facebook Login for Business configuration. If the app is Live, those permissions also need App Review. Then log out, log in with Facebook again, and reconnect the Page.');
            }

            return __('Permission denied. Log out, log in with Facebook again, and reconnect the Page so posting permissions can be granted.');
        }

        if ($this->isRateLimited()) {
            return __('Too many requests. Please try again later.');
        }

        if ($this->isInvalidInsightsMetric()) {
            return __('Facebook could not return these metrics. Please try again later.');
        }

        return __('An error occurred while communicating with Facebook. Please try again.');
    }

    public function isInvalidInsightsMetric(): bool
    {
        if ($this->code !== 100) {
            return false;
        }

        $message = strtolower($this->getMessage());

        return str_contains($message, 'insights metric')
            || str_contains($message, 'invalid metric');
    }

    public function permissionsAreUnavailable(): bool
    {
        $message = strtolower($this->getMessage());

        return str_contains($message, 'are not available')
            || str_contains($message, 'pages_manage_posts')
            || str_contains($message, 'pages_read_engagement');
    }
}

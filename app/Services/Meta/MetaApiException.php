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
        return in_array($this->code, [10, 200, 230]) || $this->errorType === 'OAuthException';
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
            return __('Permission denied. Please check your account permissions.');
        }

        if ($this->isRateLimited()) {
            return __('Too many requests. Please try again later.');
        }

        return __('An error occurred while communicating with Facebook. Please try again.');
    }
}

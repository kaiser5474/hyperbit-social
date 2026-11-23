<?php

namespace Tests\Unit;

use App\Services\Meta\MetaApiException;
use Tests\TestCase;

class MetaApiExceptionTest extends TestCase
{
    public function test_unavailable_page_permissions_explain_meta_app_setup(): void
    {
        $exception = new MetaApiException(
            '(#200) The permission(s) pages_read_engagement,pages_manage_posts are not available.',
            200,
            null,
            'OAuthException'
        );

        $this->assertTrue($exception->isPermissionError());
        $this->assertTrue($exception->permissionsAreUnavailable());
        $this->assertStringContainsString('pages_manage_posts', $exception->getUserFriendlyMessage());
        $this->assertStringContainsString('Login for Business', $exception->getUserFriendlyMessage());
    }

    public function test_generic_permission_error_asks_to_reconnect(): void
    {
        $exception = new MetaApiException(
            '(#200) Requires pages_manage_engagement permission to manage the object',
            200,
            null,
            'OAuthException'
        );

        $this->assertTrue($exception->isPermissionError());
        $message = strtolower($exception->getUserFriendlyMessage());
        $this->assertTrue(
            str_contains($message, 'reconnect') || str_contains($message, 'reconecta')
        );
    }
}

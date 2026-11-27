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
    public function test_oauth_exception_with_invalid_insights_metric_is_not_a_permission_error(): void
    {
        $exception = new MetaApiException(
            '(#100) The value must be a valid insights metric',
            100,
            null,
            'OAuthException'
        );

        $this->assertFalse($exception->isPermissionError());
        $this->assertTrue($exception->isInvalidInsightsMetric());
        $message = strtolower($exception->getUserFriendlyMessage());
        $this->assertTrue(
            str_contains($message, 'metrics') || str_contains($message, 'métricas') || str_contains($message, 'metricas')
        );
        $this->assertFalse(str_contains(strtolower($exception->getUserFriendlyMessage()), 'reconnect')
            || str_contains(strtolower($exception->getUserFriendlyMessage()), 'reconecta')
            || str_contains(strtolower($exception->getUserFriendlyMessage()), 'permission denied')
            || str_contains(strtolower($exception->getUserFriendlyMessage()), 'permiso denegado'));
    }

    public function test_code_100_without_metric_message_uses_generic_facebook_error(): void
    {
        $exception = new MetaApiException(
            '(#100) Invalid parameter',
            100,
            null,
            'OAuthException'
        );

        $this->assertFalse($exception->isPermissionError());
        $this->assertFalse($exception->isInvalidInsightsMetric());
        $this->assertStringContainsString('facebook', strtolower($exception->getUserFriendlyMessage()));
        $this->assertFalse(str_contains(strtolower($exception->getUserFriendlyMessage()), 'reconnect')
            || str_contains(strtolower($exception->getUserFriendlyMessage()), 'reconecta'));
    }

    public function test_token_error_190_asks_to_reauthenticate_not_permission_denied(): void
    {
        $exception = new MetaApiException(
            '(#190) Error validating access token',
            190,
            463,
            'OAuthException'
        );

        $this->assertFalse($exception->isPermissionError());
        $this->assertTrue($exception->isTokenExpired());
        $message = strtolower($exception->getUserFriendlyMessage());
        $this->assertTrue(str_contains($message, 'expired') || str_contains($message, 'expirado'));
        $this->assertFalse(str_contains($message, 'permission denied') || str_contains($message, 'permiso denegado'));
    }

    public function test_permission_code_10_is_a_permission_error(): void
    {
        $exception = new MetaApiException(
            '(#10) Application does not have permission for this action',
            10,
            null,
            'OAuthException'
        );

        $this->assertTrue($exception->isPermissionError());
    }
}

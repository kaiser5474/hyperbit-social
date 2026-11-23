<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphClient
{
    protected string $baseUrl;

    protected string $version;

    protected string $appId;

    protected ?string $appSecret;

    public function __construct()
    {
        $this->version = config('meta.graph_version', 'v22.0');
        $this->baseUrl = config('meta.graph_url', 'https://graph.facebook.com');
        $this->appId = config('meta.app_id') ?? '';
        $this->appSecret = config('meta.app_secret');
    }

    protected function requireAppSecret(): string
    {
        if (empty($this->appSecret)) {
            throw new \RuntimeException('META_APP_SECRET is not configured. Set it in your .env file.');
        }

        return $this->appSecret;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(30)
            ->retry(2, 100);
    }

    public function getOAuthUrl(string $redirectUri, array $scopes = []): string
    {
        $scopes = $scopes ?: config('meta.scopes', []);
        $params = http_build_query([
            'client_id' => $this->appId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => csrf_token(),
        ]);

        return config('meta.oauth_url')."/{$this->version}/dialog/oauth?{$params}";
    }

    public function exchangeCodeForToken(string $code, string $redirectUri): array
    {
        $response = $this->client()->get("/{$this->version}/oauth/access_token", [
            'client_id' => $this->appId,
            'client_secret' => $this->requireAppSecret(),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        return $this->handleResponse($response, 'exchangeCodeForToken');
    }

    public function getLongLivedToken(string $shortLivedToken): array
    {
        $response = $this->client()->get("/{$this->version}/oauth/access_token", [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->requireAppSecret(),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        return $this->handleResponse($response, 'getLongLivedToken');
    }

    public function getMe(string $accessToken, array $fields = ['id', 'name', 'email', 'picture']): array
    {
        $response = $this->client()->get("/{$this->version}/me", [
            'access_token' => $accessToken,
            'fields' => implode(',', $fields),
        ]);

        return $this->handleResponse($response, 'getMe');
    }

    public function getPages(string $userAccessToken): array
    {
        $response = $this->client()->get("/{$this->version}/me/accounts", [
            'access_token' => $userAccessToken,
            'fields' => 'id,name,access_token,category,picture,instagram_business_account',
        ]);

        return $this->handleResponse($response, 'getPages');
    }

    public function getPageLongLivedToken(string $pageId, string $userAccessToken): array
    {
        $response = $this->client()->get("/{$this->version}/{$pageId}", [
            'access_token' => $userAccessToken,
            'fields' => 'access_token',
        ]);

        return $this->handleResponse($response, 'getPageLongLivedToken');
    }

    public function getInstagramAccount(string $instagramBusinessAccountId, string $accessToken): array
    {
        $response = $this->client()->get("/{$this->version}/{$instagramBusinessAccountId}", [
            'access_token' => $accessToken,
            'fields' => 'id,username,name,profile_picture_url,followers_count,media_count',
        ]);

        return $this->handleResponse($response, 'getInstagramAccount');
    }

    public function publishToFacebookFeed(string $pageId, string $accessToken, string $message, ?string $link = null): array
    {
        $params = [
            'access_token' => $accessToken,
            'message' => $message,
        ];

        if ($link) {
            $params['link'] = $link;
        }

        $response = $this->client()->post("/{$this->version}/{$pageId}/feed", $params);

        return $this->handleResponse($response, 'publishToFacebookFeed');
    }

    public function publishPhotoToFacebook(string $pageId, string $accessToken, string $imageUrl, ?string $caption = null): array
    {
        $params = [
            'access_token' => $accessToken,
            'url' => $imageUrl,
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        $response = $this->client()->post("/{$this->version}/{$pageId}/photos", $params);

        return $this->handleResponse($response, 'publishPhotoToFacebook');
    }

    public function createInstagramMediaContainer(
        string $instagramAccountId,
        string $accessToken,
        string $imageUrl,
        ?string $caption = null
    ): array {
        $params = [
            'access_token' => $accessToken,
            'image_url' => $imageUrl,
        ];

        if ($caption) {
            $params['caption'] = $caption;
        }

        $response = $this->client()->post("/{$this->version}/{$instagramAccountId}/media", $params);

        return $this->handleResponse($response, 'createInstagramMediaContainer');
    }

    public function getInstagramContainerStatus(string $containerId, string $accessToken): array
    {
        $response = $this->client()->get("/{$this->version}/{$containerId}", [
            'access_token' => $accessToken,
            'fields' => 'status_code,status',
        ]);

        return $this->handleResponse($response, 'getInstagramContainerStatus');
    }

    public function publishInstagramMedia(string $instagramAccountId, string $accessToken, string $containerId): array
    {
        $response = $this->client()->post("/{$this->version}/{$instagramAccountId}/media_publish", [
            'access_token' => $accessToken,
            'creation_id' => $containerId,
        ]);

        return $this->handleResponse($response, 'publishInstagramMedia');
    }

    public function getPostPermalink(string $postId, string $accessToken): ?string
    {
        try {
            $response = $this->client()->get("/{$this->version}/{$postId}", [
                'access_token' => $accessToken,
                'fields' => 'permalink_url',
            ]);

            $data = $this->handleResponse($response, 'getPostPermalink');

            return $data['permalink_url'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to get permalink', ['post_id' => $postId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function debugToken(string $accessToken): array
    {
        $response = $this->client()->get("/{$this->version}/debug_token", [
            'input_token' => $accessToken,
            'access_token' => $this->appId.'|'.$this->requireAppSecret(),
        ]);

        return $this->handleResponse($response, 'debugToken');
    }

    public function parseSignedRequest(string $signedRequest): ?array
    {
        $parts = explode('.', $signedRequest, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedSig, $payload] = $parts;

        $sig = $this->base64UrlDecode($encodedSig);
        $data = json_decode($this->base64UrlDecode($payload), true);

        if (! $data) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $payload, $this->requireAppSecret(), true);

        if (! hash_equals($sig, $expectedSig)) {
            Log::warning('Invalid signed_request signature');

            return null;
        }

        return $data;
    }

    protected function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }

    protected function handleResponse(Response $response, string $method): array
    {
        $data = $response->json();

        if ($response->failed() || isset($data['error'])) {
            $error = $data['error'] ?? ['message' => 'Unknown error', 'code' => $response->status()];

            Log::error("Meta API Error [{$method}]", [
                'error' => $error,
                'status' => $response->status(),
            ]);

            throw new MetaApiException(
                $error['message'] ?? 'Unknown error',
                $error['code'] ?? $response->status(),
                $error['error_subcode'] ?? null,
                $error['type'] ?? null
            );
        }

        return $data;
    }
}

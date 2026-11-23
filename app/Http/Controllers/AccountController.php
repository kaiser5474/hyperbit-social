<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function index(): View
    {
        $accounts = auth()->user()->socialAccounts()->get();

        return view('accounts.index', compact('accounts'));
    }

    public function connect(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user->facebook_token || $user->facebook_token_expires_at?->isPast()) {
            return redirect()->route('auth.facebook')
                ->with('info', __('Please log in with Facebook again to connect accounts.'));
        }

        try {
            $pagesResponse = $this->client->getPages($user->facebook_token);
            $pages = is_array($pagesResponse['data'] ?? null) ? $pagesResponse['data'] : [];

            $availablePages = [];

            foreach ($pages as $page) {
                if (! is_array($page) || empty($page['id'])) {
                    continue;
                }

                $isConnected = $user->socialAccounts()
                    ->where('platform', SocialAccount::PLATFORM_FACEBOOK_PAGE)
                    ->where('platform_id', $page['id'])
                    ->exists();

                $instagramAccount = null;
                if (isset($page['instagram_business_account']['id']) && ! empty($page['access_token'])) {
                    try {
                        $instagramAccount = $this->client->getInstagramAccount(
                            $page['instagram_business_account']['id'],
                            $page['access_token']
                        );

                        $instagramAccount['is_connected'] = $user->socialAccounts()
                            ->where('platform', SocialAccount::PLATFORM_INSTAGRAM_BUSINESS)
                            ->where('platform_id', $instagramAccount['id'])
                            ->exists();
                    } catch (\Exception $e) {
                        Log::warning('Failed to fetch Instagram account', [
                            'page_id' => $page['id'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $availablePages[] = [
                    'page' => $page,
                    'is_connected' => $isConnected,
                    'instagram' => $instagramAccount,
                ];
            }

            return view('accounts.connect', compact('availablePages'));

        } catch (MetaApiException $e) {
            Log::error('Failed to fetch pages', ['error' => $e->getMessage()]);

            return redirect()->route('accounts.index')
                ->with('error', $e->getUserFriendlyMessage());
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching pages', ['error' => $e->getMessage()]);

            return redirect()->route('accounts.index')
                ->with('error', __('An error occurred while communicating with Facebook. Please try again.'));
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'accounts' => 'required|array|min:1',
            'accounts.*' => 'required|string',
        ], [
            'accounts.required' => __('Please select at least one account to connect.'),
            'accounts.min' => __('Please select at least one account to connect.'),
        ]);

        $user = auth()->user();

        if (! $user->facebook_token || $user->facebook_token_expires_at?->isPast()) {
            return redirect()->route('auth.facebook')
                ->with('info', __('Please log in with Facebook again to connect accounts.'));
        }

        try {
            $pagesResponse = $this->client->getPages($user->facebook_token);
            $pages = collect($pagesResponse['data'] ?? [])
                ->filter(fn ($page) => is_array($page) && isset($page['id']))
                ->keyBy(fn (array $page) => (string) $page['id']);
        } catch (MetaApiException $e) {
            Log::error('Failed to fetch pages for account connection', ['error' => $e->getMessage()]);

            return redirect()->route('accounts.connect')
                ->with('error', $e->getUserFriendlyMessage());
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching pages for account connection', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('accounts.connect')
                ->with('error', __('An error occurred while communicating with Facebook. Please try again.'));
        }

        $connected = 0;

        foreach ($request->accounts as $accountId) {
            $accountId = (string) $accountId;

            try {
                if (str_starts_with($accountId, 'fb_')) {
                    $pageId = substr($accountId, 3);
                    $page = $pages->get($pageId);

                    if (! $this->connectFacebookPage($user, is_array($page) ? $page : [])) {
                        continue;
                    }

                    $connected++;
                } elseif (str_starts_with($accountId, 'ig_')) {
                    $parts = explode('_', $accountId, 3);
                    if (count($parts) < 3) {
                        continue;
                    }

                    $igId = $parts[1];
                    $parentPageId = $parts[2];
                    $parentPage = $pages->get($parentPageId);

                    if (! is_array($parentPage) || ! isset($parentPage['instagram_business_account']['id'])) {
                        continue;
                    }

                    if ((string) $parentPage['instagram_business_account']['id'] !== $igId) {
                        continue;
                    }

                    if (empty($parentPage['access_token'])) {
                        Log::warning('Skipping Instagram account because parent page has no access token', [
                            'ig_id' => $igId,
                            'page_id' => $parentPageId,
                        ]);

                        continue;
                    }

                    $instagram = $this->client->getInstagramAccount($igId, $parentPage['access_token']);

                    if (! $this->connectInstagramAccount($user, $instagram, $parentPage)) {
                        continue;
                    }

                    $connected++;
                }
            } catch (\Throwable $e) {
                Log::error('Failed to connect social account', [
                    'account_id' => $accountId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($connected === 0) {
            return redirect()->route('accounts.connect')
                ->with('error', __('No accounts could be connected. Please try again.'));
        }

        return redirect()->route('accounts.index')
            ->with('success', trans_choice(':count account connected.|:count accounts connected.', $connected, ['count' => $connected]));
    }

    protected function connectFacebookPage($user, array $page): bool
    {
        $pageId = isset($page['id']) ? (string) $page['id'] : '';
        $accessToken = $page['access_token'] ?? null;

        if ($pageId === '' || ! is_string($accessToken) || $accessToken === '') {
            Log::warning('Skipping Facebook Page without id or access token', [
                'page_id' => $pageId !== '' ? $pageId : null,
            ]);

            return false;
        }

        $existing = $user->socialAccounts()
            ->where('platform', SocialAccount::PLATFORM_FACEBOOK_PAGE)
            ->where('platform_id', $pageId)
            ->first();

        $data = [
            'access_token' => $accessToken,
            'platform_name' => $page['name'] ?? ('Facebook Page '.$pageId),
            'avatar_url' => $page['picture']['data']['url'] ?? null,
            'is_active' => true,
            'last_error' => null,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            $user->socialAccounts()->create(array_merge($data, [
                'platform' => SocialAccount::PLATFORM_FACEBOOK_PAGE,
                'platform_id' => $pageId,
            ]));
        }

        return true;
    }

    protected function connectInstagramAccount($user, array $instagram, array $parentPage): bool
    {
        $instagramId = isset($instagram['id']) ? (string) $instagram['id'] : '';
        $accessToken = $parentPage['access_token'] ?? null;

        if ($instagramId === '' || ! is_string($accessToken) || $accessToken === '') {
            Log::warning('Skipping Instagram account without id or access token', [
                'ig_id' => $instagramId !== '' ? $instagramId : null,
                'page_id' => $parentPage['id'] ?? null,
            ]);

            return false;
        }

        $existing = $user->socialAccounts()
            ->where('platform', SocialAccount::PLATFORM_INSTAGRAM_BUSINESS)
            ->where('platform_id', $instagramId)
            ->first();

        $data = [
            'access_token' => $accessToken,
            'platform_name' => $instagram['name'] ?? $instagram['username'] ?? ('Instagram '.$instagramId),
            'platform_username' => $instagram['username'] ?? null,
            'avatar_url' => $instagram['profile_picture_url'] ?? null,
            'parent_page_id' => isset($parentPage['id']) ? (string) $parentPage['id'] : null,
            'is_active' => true,
            'last_error' => null,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            $user->socialAccounts()->create(array_merge($data, [
                'platform' => SocialAccount::PLATFORM_INSTAGRAM_BUSINESS,
                'platform_id' => $instagramId,
            ]));
        }

        return true;
    }

    public function destroy(SocialAccount $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $name = $account->platform_name;
        $account->delete();

        return redirect()->route('accounts.index')
            ->with('success', __(':name has been disconnected.', ['name' => $name]));
    }

    public function refresh(SocialAccount $account): RedirectResponse
    {
        $this->authorize('update', $account);

        $user = auth()->user();

        if (! $user->facebook_token || $user->facebook_token_expires_at?->isPast()) {
            return redirect()->route('auth.facebook')
                ->with('info', __('Please log in with Facebook again to refresh tokens.'));
        }

        try {
            if ($account->isFacebookPage()) {
                $pageData = $this->client->getPageLongLivedToken(
                    $account->platform_id,
                    $user->facebook_token
                );

                $account->update([
                    'access_token' => $pageData['access_token'],
                    'last_error' => null,
                ]);
            }

            return redirect()->route('accounts.index')
                ->with('success', __(':name token has been refreshed.', ['name' => $account->platform_name]));

        } catch (MetaApiException $e) {
            Log::error('Failed to refresh token', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('accounts.index')
                ->with('error', $e->getUserFriendlyMessage());
        }
    }
}

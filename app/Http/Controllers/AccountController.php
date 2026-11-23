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
            $pages = $pagesResponse['data'] ?? [];

            $availablePages = [];

            foreach ($pages as $page) {
                $isConnected = $user->socialAccounts()
                    ->where('platform', SocialAccount::PLATFORM_FACEBOOK_PAGE)
                    ->where('platform_id', $page['id'])
                    ->exists();

                $instagramAccount = null;
                if (isset($page['instagram_business_account']['id'])) {
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
            $pages = collect($pagesResponse['data'] ?? [])->keyBy('id');
        } catch (MetaApiException $e) {
            Log::error('Failed to fetch pages for account connection', ['error' => $e->getMessage()]);

            return redirect()->route('accounts.connect')
                ->with('error', $e->getUserFriendlyMessage());
        }

        $connected = 0;

        foreach ($request->accounts as $accountId) {
            if (str_starts_with($accountId, 'fb_')) {
                $pageId = substr($accountId, 3);
                $page = $pages->get($pageId);

                if (! $page) {
                    continue;
                }

                $this->connectFacebookPage($user, $page);
                $connected++;

            } elseif (str_starts_with($accountId, 'ig_')) {
                $parts = explode('_', $accountId, 3);
                if (count($parts) < 3) {
                    continue;
                }

                $igId = $parts[1];
                $parentPageId = $parts[2];
                $parentPage = $pages->get($parentPageId);

                if (! $parentPage || ! isset($parentPage['instagram_business_account']['id'])) {
                    continue;
                }

                if ($parentPage['instagram_business_account']['id'] !== $igId) {
                    continue;
                }

                try {
                    $instagram = $this->client->getInstagramAccount($igId, $parentPage['access_token']);
                    $this->connectInstagramAccount($user, $instagram, $parentPage);
                    $connected++;
                } catch (\Exception $e) {
                    Log::warning('Failed to connect Instagram account', [
                        'ig_id' => $igId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($connected === 0) {
            return redirect()->route('accounts.connect')
                ->with('error', __('No accounts could be connected. Please try again.'));
        }

        return redirect()->route('accounts.index')
            ->with('success', trans_choice(':count account connected.|:count accounts connected.', $connected, ['count' => $connected]));
    }

    protected function connectFacebookPage($user, array $page): void
    {
        $existing = $user->socialAccounts()
            ->where('platform', SocialAccount::PLATFORM_FACEBOOK_PAGE)
            ->where('platform_id', $page['id'])
            ->first();

        $data = [
            'access_token' => $page['access_token'],
            'platform_name' => $page['name'],
            'avatar_url' => $page['picture']['data']['url'] ?? null,
            'is_active' => true,
            'last_error' => null,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            $user->socialAccounts()->create(array_merge($data, [
                'platform' => SocialAccount::PLATFORM_FACEBOOK_PAGE,
                'platform_id' => $page['id'],
            ]));
        }
    }

    protected function connectInstagramAccount($user, array $instagram, array $parentPage): void
    {
        $existing = $user->socialAccounts()
            ->where('platform', SocialAccount::PLATFORM_INSTAGRAM_BUSINESS)
            ->where('platform_id', $instagram['id'])
            ->first();

        $data = [
            'access_token' => $parentPage['access_token'],
            'platform_name' => $instagram['name'] ?? $instagram['username'],
            'platform_username' => $instagram['username'],
            'avatar_url' => $instagram['profile_picture_url'] ?? null,
            'parent_page_id' => $parentPage['id'],
            'is_active' => true,
            'last_error' => null,
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            $user->socialAccounts()->create(array_merge($data, [
                'platform' => SocialAccount::PLATFORM_INSTAGRAM_BUSINESS,
                'platform_id' => $instagram['id'],
            ]));
        }
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

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
            'accounts.*.type' => 'required|in:facebook_page,instagram_business',
            'accounts.*.id' => 'required|string',
            'accounts.*.name' => 'required|string',
            'accounts.*.token' => 'required|string',
            'accounts.*.username' => 'nullable|string',
            'accounts.*.avatar' => 'nullable|string',
            'accounts.*.parent_page_id' => 'nullable|string',
        ]);

        $user = auth()->user();
        $connected = 0;

        foreach ($request->accounts as $accountData) {
            $existing = $user->socialAccounts()
                ->where('platform', $accountData['type'])
                ->where('platform_id', $accountData['id'])
                ->first();

            if ($existing) {
                $existing->update([
                    'access_token' => $accountData['token'],
                    'platform_name' => $accountData['name'],
                    'platform_username' => $accountData['username'] ?? null,
                    'avatar_url' => $accountData['avatar'] ?? null,
                    'is_active' => true,
                    'last_error' => null,
                ]);
            } else {
                $user->socialAccounts()->create([
                    'platform' => $accountData['type'],
                    'platform_id' => $accountData['id'],
                    'platform_name' => $accountData['name'],
                    'platform_username' => $accountData['username'] ?? null,
                    'avatar_url' => $accountData['avatar'] ?? null,
                    'parent_page_id' => $accountData['parent_page_id'] ?? null,
                    'access_token' => $accountData['token'],
                    'is_active' => true,
                ]);
            }

            $connected++;
        }

        return redirect()->route('accounts.index')
            ->with('success', trans_choice(':count account connected.|:count accounts connected.', $connected, ['count' => $connected]));
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

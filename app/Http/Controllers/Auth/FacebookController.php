<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacebookController extends Controller
{
    public const OAUTH_STATE_SESSION_KEY = 'oauth_state';

    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        $request->session()->put(self::OAUTH_STATE_SESSION_KEY, $state);

        $redirectUri = config('meta.redirect_uri');
        $url = $this->client->getOAuthUrl($redirectUri, state: $state);

        return redirect()->away($url);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning('Facebook OAuth error', [
                'error' => $request->get('error'),
                'error_reason' => $request->get('error_reason'),
                'error_description' => $request->get('error_description'),
            ]);

            $request->session()->forget(self::OAUTH_STATE_SESSION_KEY);

            return redirect()->route('login')
                ->with('error', __('Facebook login was cancelled or failed.'));
        }

        if (! $this->isValidOAuthState($request)) {
            Log::warning('Facebook OAuth invalid state');

            return redirect()->route('login')
                ->with('error', __('Invalid OAuth state. Please try logging in again.'));
        }

        $code = $request->get('code');

        if (! $code) {
            return redirect()->route('login')
                ->with('error', __('Invalid response from Facebook.'));
        }

        try {
            $tokenData = $this->client->exchangeCodeForToken(
                $code,
                config('meta.redirect_uri')
            );

            $shortLivedToken = $tokenData['access_token'];
            $longLivedData = $this->client->getLongLivedToken($shortLivedToken);

            $accessToken = $longLivedData['access_token'];
            $expiresIn = $longLivedData['expires_in'] ?? 5184000;

            $userData = $this->client->getMe($accessToken, [
                'id',
                'name',
                'picture.width(200).height(200)',
            ]);

            $user = $this->findOrCreateUser($userData, $accessToken, $expiresIn);

            Auth::login($user, true);
            $request->session()->regenerate();

            Log::info('User logged in via Facebook', [
                'user_id' => $user->id,
                'facebook_id' => $user->facebook_id,
            ]);

            return redirect()->intended(route('dashboard'))
                ->with('success', __('Welcome back, :name!', ['name' => $user->name]));

        } catch (MetaApiException $e) {
            Log::error('Facebook OAuth API error', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return redirect()->route('login')
                ->with('error', $e->getUserFriendlyMessage());

        } catch (\Exception $e) {
            Log::error('Facebook OAuth unexpected error', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', __('An unexpected error occurred. Please try again.'));
        }
    }

    protected function isValidOAuthState(Request $request): bool
    {
        $expected = $request->session()->pull(self::OAUTH_STATE_SESSION_KEY);
        $provided = $request->get('state');

        if (! is_string($expected) || $expected === '' || ! is_string($provided) || $provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    protected function findOrCreateUser(array $userData, string $accessToken, int $expiresIn): User
    {
        $facebookId = $userData['id'];
        $name = $userData['name'];
        $avatar = $userData['picture']['data']['url'] ?? null;

        $user = User::where('facebook_id', $facebookId)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'facebook_token' => $accessToken,
                'facebook_token_expires_at' => now()->addSeconds($expiresIn),
                'avatar' => $avatar,
            ]);

            return $user;
        }

        return User::create([
            'name' => $name,
            'email' => $facebookId.'@fb.hyperbitit.com',
            'facebook_id' => $facebookId,
            'facebook_token' => $accessToken,
            'facebook_token_expires_at' => now()->addSeconds($expiresIn),
            'avatar' => $avatar,
            'timezone' => 'America/New_York',
            'locale' => app()->getLocale(),
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', __('You have been logged out.'));
    }
}

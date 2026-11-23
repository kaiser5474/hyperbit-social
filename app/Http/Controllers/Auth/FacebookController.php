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

class FacebookController extends Controller
{
    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function redirect(): RedirectResponse
    {
        $redirectUri = config('meta.redirect_uri');
        $url = $this->client->getOAuthUrl($redirectUri);

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

            return redirect()->route('login')
                ->with('error', __('Facebook login was cancelled or failed.'));
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
                'email',
                'picture.width(200).height(200)',
            ]);

            $user = $this->findOrCreateUser($userData, $accessToken, $expiresIn);

            Auth::login($user, true);

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

    protected function findOrCreateUser(array $userData, string $accessToken, int $expiresIn): User
    {
        $facebookId = $userData['id'];
        $email = $userData['email'] ?? $facebookId.'@facebook.com';
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

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $existingUser->update([
                'facebook_id' => $facebookId,
                'facebook_token' => $accessToken,
                'facebook_token_expires_at' => now()->addSeconds($expiresIn),
                'avatar' => $avatar,
            ]);

            return $existingUser;
        }

        return User::create([
            'name' => $name,
            'email' => $email,
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

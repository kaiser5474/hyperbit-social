<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\SocialAccount;
use App\Policies\PostPolicy;
use App\Policies\SocialAccountPolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);

        $this->configureLocale();
    }

    protected function configureLocale(): void
    {
        $locale = session('locale', config('app.locale', 'es'));

        if (auth()->check() && auth()->user()->locale) {
            $locale = auth()->user()->locale;
        }

        App::setLocale($locale);
    }
}

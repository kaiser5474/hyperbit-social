<?php

namespace App\Providers;

use App\Services\Meta\MetaGraphClient;
use App\Services\Publishers\FacebookPagePublisher;
use App\Services\Publishers\InstagramPublisher;
use App\Services\Publishers\PublisherManager;
use App\Services\Publishers\ThreadsPublisher;
use Illuminate\Support\ServiceProvider;

class MetaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MetaGraphClient::class, function () {
            return new MetaGraphClient;
        });

        $this->app->singleton(FacebookPagePublisher::class, function ($app) {
            return new FacebookPagePublisher($app->make(MetaGraphClient::class));
        });

        $this->app->singleton(InstagramPublisher::class, function ($app) {
            return new InstagramPublisher($app->make(MetaGraphClient::class));
        });

        $this->app->singleton(ThreadsPublisher::class, function () {
            return new ThreadsPublisher;
        });

        $this->app->singleton(PublisherManager::class, function ($app) {
            return new PublisherManager(
                $app->make(FacebookPagePublisher::class),
                $app->make(InstagramPublisher::class),
                $app->make(ThreadsPublisher::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}

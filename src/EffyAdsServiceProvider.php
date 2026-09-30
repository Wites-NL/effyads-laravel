<?php

namespace EffyAds\Laravel;

use EffyAds\Laravel\Console\Commands\InstallCommand;
use EffyAds\Laravel\View\Components\EffyAd;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class EffyAdsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/effyads.php', 'effyads');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'effyads');

        Blade::component(EffyAd::class, 'effyads::ad');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);

            $this->publishes([
                __DIR__.'/../config/effyads.php' => config_path('effyads.php'),
            ], 'effyads-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'effyads-migrations');
        }
    }
}

<?php

namespace EffyAds\Laravel;

use EffyAds\Laravel\Console\Commands\InstallCommand;
use EffyAds\Laravel\Support\ActiveAdvertisements;
use EffyAds\Laravel\View\Components\EffyAd;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class EffyAdsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/effyads.php', 'effyads');

        $this->app->scoped(ActiveAdvertisements::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

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

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('effyads-api', fn (Request $request) => Limit::perMinute(
            (int) config('effyads.api_rate_limit', 120)
        )->by('effyads-api|'.$request->ip()));

        RateLimiter::for('effyads-tracking', fn (Request $request) => Limit::perMinute(
            (int) config('effyads.tracking.rate_limit', 60)
        )->by('effyads-tracking|'.$request->ip()));
    }
}

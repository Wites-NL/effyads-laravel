<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Tests\Fixtures\IdentifyTenant;
use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class RouteMiddlewareTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('effyads.route_middleware', [IdentifyTenant::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        IdentifyTenant::$current = null;
        // Like tenant()?->getTenantKey(): only works once the middleware ran.
        EffyAds::resolveSiteUsing(fn () => IdentifyTenant::$current);
        EffyAds::resolveApiKeyUsing(fn (?string $site) => $site === 'assen' ? 'assen-key' : null);
    }

    public function test_configured_middleware_runs_before_the_api_key_check(): void
    {
        $this->getJson('/effyads/v1/sync/status', ['X-Tenant' => 'assen', 'X-EffyAds-API-Key' => 'assen-key'])
            ->assertOk()
            ->assertJson(['site' => 'assen']);
    }

    public function test_configured_middleware_runs_on_tracking_routes(): void
    {
        Http::fake();
        $this->createAd(['site' => 'assen']);

        $this->get('/effyads/track/click/ad-1', ['X-Tenant' => 'assen'])->assertRedirect();
        $this->get('/effyads/track/click/ad-1')->assertNotFound();
    }
}

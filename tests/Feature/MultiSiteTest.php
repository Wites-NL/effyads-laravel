<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Models\Advertisement;
use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MultiSiteTest extends TestCase
{
    private const KEYS = [
        'groningen' => 'key-groningen-0000000000',
        'assen' => 'key-assen-111111111111111',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Site = first label of the host, as a tenancy package would resolve it.
        EffyAds::resolveSiteUsing(fn (Request $request) => strtok($request->getHost(), '.') ?: null);
        EffyAds::resolveApiKeyUsing(fn (?string $site) => self::KEYS[$site] ?? null);
    }

    private function url(string $site, string $path): string
    {
        return "http://{$site}.example.test{$path}";
    }

    private function headers(string $site): array
    {
        return ['X-EffyAds-API-Key' => self::KEYS[$site]];
    }

    private function storeFor(string $site, array $overrides = []): void
    {
        $this->postJson($this->url($site, '/effyads/v1/advertisements'), $this->adPayload($overrides), $this->headers($site))
            ->assertCreated();
    }

    public function test_key_of_site_a_does_not_work_for_site_b(): void
    {
        $this->getJson($this->url('assen', '/effyads/v1/sync/status'), $this->headers('groningen'))->assertForbidden();
        $this->getJson($this->url('assen', '/effyads/v1/sync/status'), $this->headers('assen'))->assertOk();
    }

    public function test_global_config_key_is_not_accepted_when_a_key_resolver_is_set(): void
    {
        $this->getJson($this->url('assen', '/effyads/v1/sync/status'), $this->apiHeaders())->assertForbidden();
    }

    public function test_store_tags_the_advertisement_with_the_site(): void
    {
        $this->storeFor('groningen');

        $this->assertSame('groningen', Advertisement::first()->site);
    }

    public function test_same_platform_id_can_exist_on_two_sites(): void
    {
        $this->storeFor('groningen', ['title' => 'Groningen']);
        $this->storeFor('assen', ['title' => 'Assen']);

        $this->assertSame(2, Advertisement::count());
    }

    public function test_advertisement_of_site_a_is_not_visible_via_the_api_of_site_b(): void
    {
        $this->storeFor('groningen');

        $this->getJson($this->url('assen', '/effyads/v1/advertisements'), $this->headers('assen'))
            ->assertOk()
            ->assertJson(['count' => 0]);

        $this->getJson($this->url('assen', '/effyads/v1/advertisements/ad-1'), $this->headers('assen'))->assertNotFound();
        $this->putJson($this->url('assen', '/effyads/v1/advertisements/ad-1'), $this->adPayload(), $this->headers('assen'))->assertNotFound();
        $this->deleteJson($this->url('assen', '/effyads/v1/advertisements/ad-1'), [], $this->headers('assen'))->assertNotFound();

        $this->getJson($this->url('groningen', '/effyads/v1/advertisements/ad-1'), $this->headers('groningen'))->assertOk();
    }

    public function test_advertisement_of_site_a_is_not_rendered_on_site_b(): void
    {
        $this->storeFor('groningen');

        $this->app->instance('request', Request::create($this->url('assen', '/')));
        $this->assertSame('', trim((string) $this->blade('<x-effyads::ad zone="homepage-hero" />')));

        $this->app->instance('request', Request::create($this->url('groningen', '/')));
        $this->assertStringContainsString('effyads-ad', (string) $this->blade('<x-effyads::ad zone="homepage-hero" />'));
    }

    public function test_click_on_another_sites_advertisement_is_not_found(): void
    {
        Http::fake();
        $this->storeFor('groningen');

        $this->get($this->url('assen', '/effyads/track/click/ad-1'))->assertNotFound();
        $this->get($this->url('groningen', '/effyads/track/click/ad-1'))->assertRedirect('https://advertiser.test/landing');

        Http::assertSent(fn (ClientRequest $r) => $r['site_url'] === 'http://groningen.example.test');
    }

    public function test_bulk_sync_on_site_a_leaves_site_b_alone(): void
    {
        $this->storeFor('groningen', ['platform_id' => 'g-1']);
        $this->storeFor('groningen', ['platform_id' => 'g-2']);
        $this->storeFor('assen', ['platform_id' => 'a-1']);

        $this->postJson($this->url('groningen', '/effyads/v1/sync/bulk'), ['platform_ids' => ['g-1']], $this->headers('groningen'))
            ->assertOk()
            ->assertJson(['site' => 'groningen', 'deleted_count' => 1, 'local_ads_count' => 2]);

        $this->assertEqualsCanonicalizing(['g-1', 'a-1'], Advertisement::pluck('platform_id')->all());
    }

    public function test_sync_status_reports_site_and_site_url(): void
    {
        $this->storeFor('assen');

        $this->getJson($this->url('assen', '/effyads/v1/sync/status'), $this->headers('assen'))
            ->assertOk()
            ->assertJson([
                'site' => 'assen',
                'site_url' => 'http://assen.example.test',
                'total_ads' => 1,
            ]);
    }

    public function test_install_with_site_only_prints_a_key(): void
    {
        $this->artisan('effyads:install', ['--site' => 'assen'])
            ->expectsOutputToContain('New API key for site assen')
            ->doesntExpectOutputToContain('Published config')
            ->assertSuccessful();
    }
}

<?php

namespace EffyAds\Laravel\Tests;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\EffyAdsServiceProvider;
use EffyAds\Laravel\Models\Advertisement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected const API_KEY = 'test-api-key-0123456789';

    protected function tearDown(): void
    {
        EffyAds::flushResolvers();

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [EffyAdsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('effyads.api_key', self::API_KEY);
        $app['config']->set('effyads.platform_url', 'https://platform.test');
        $app['config']->set('effyads.zones', [
            'homepage-hero' => 'Homepage hero',
            'sidebar' => 'Sidebar',
        ]);
    }

    protected function apiHeaders(?string $key = self::API_KEY): array
    {
        return $key === null ? [] : ['X-EffyAds-API-Key' => $key];
    }

    protected function adPayload(array $overrides = []): array
    {
        return array_merge([
            'platform_id' => 'ad-1',
            'title' => 'Test ad',
            'description' => 'Description',
            'link' => 'https://advertiser.test/landing',
            'media_url' => 'https://cdn.test/banner.png',
            'status' => 'active',
            'targeting' => ['zones' => ['include' => ['homepage-hero']]],
        ], $overrides);
    }

    protected function createAd(array $overrides = []): Advertisement
    {
        return Advertisement::create($this->adPayload($overrides));
    }
}

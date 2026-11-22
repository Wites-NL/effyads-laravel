<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;

class ApiKeyTest extends TestCase
{
    public function test_request_without_key_is_rejected(): void
    {
        $this->getJson('/effyads/v1/sync/status')->assertForbidden();
    }

    public function test_request_with_wrong_key_is_rejected(): void
    {
        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders('wrong'))->assertForbidden();
    }

    public function test_request_with_correct_key_is_accepted(): void
    {
        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders())
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_requests_are_rejected_when_no_key_is_configured(): void
    {
        config(['effyads.api_key' => null]);

        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders(''))->assertForbidden();
    }

    public function test_sync_status_reports_the_installed_package_version(): void
    {
        $version = \Composer\InstalledVersions::getPrettyVersion('effyads/laravel');

        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('package_version', $version);
    }

    public function test_sync_status_reports_no_site_without_resolvers(): void
    {
        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders())
            ->assertOk()
            ->assertJson(['site' => null, 'site_url' => 'http://localhost']);
    }
}

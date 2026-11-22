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
}

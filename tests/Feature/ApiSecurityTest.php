<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class ApiSecurityTest extends TestCase
{
    public function test_query_api_key_is_rejected_by_default(): void
    {
        $this->getJson('/effyads/v1/sync/status?api_key='.self::API_KEY)->assertForbidden();
    }

    public function test_query_api_key_works_when_allowed_and_logs_a_deprecation(): void
    {
        config(['effyads.allow_query_api_key' => true]);
        Log::spy();

        $this->getJson('/effyads/v1/sync/status?api_key='.self::API_KEY)->assertOk();

        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'deprecated'))->once();
    }

    public function test_failures_give_no_hint_about_the_reason(): void
    {
        $missing = $this->getJson('/effyads/v1/sync/status')->assertForbidden()->json();
        $wrong = $this->getJson('/effyads/v1/sync/status', $this->apiHeaders('wrong'))->assertForbidden()->json();

        config(['effyads.api_key' => null]);
        $unconfigured = $this->getJson('/effyads/v1/sync/status', $this->apiHeaders('anything'))->assertForbidden()->json();

        $this->assertSame($missing, $wrong);
        $this->assertSame($missing, $unconfigured);
    }

    public function test_api_is_rate_limited_per_ip(): void
    {
        config(['effyads.api_rate_limit' => 2]);

        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders('wrong'))->assertForbidden();
        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders('wrong'))->assertForbidden();
        $this->getJson('/effyads/v1/sync/status', $this->apiHeaders())->assertStatus(429);
    }
}

<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;
use EffyAds\Laravel\Jobs\RecordTrackingEvent;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

class TrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['platform.test/*' => Http::response(['success' => true])]);
    }

    public function test_view_pixel_returns_a_gif_and_reports_the_view(): void
    {
        $this->createAd();

        $this->get('/effyads/track/view/ad-1')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://platform.test/api/tracking'
            && $request['platform_id'] === 'ad-1'
            && $request['event_type'] === 'view');
    }

    public function test_click_redirects_to_the_link_and_reports_the_click(): void
    {
        $this->createAd();

        $this->get('/effyads/track/click/ad-1')
            ->assertRedirect('https://advertiser.test/landing');

        Http::assertSent(fn (Request $request) => $request->url() === 'https://platform.test/api/tracking'
            && $request['platform_id'] === 'ad-1'
            && $request['event_type'] === 'click');
    }

    public function test_click_on_unknown_ad_is_not_found(): void
    {
        $this->get('/effyads/track/click/missing')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_sync_default_sends_the_event_after_the_response(): void
    {
        Bus::fake();
        $this->createAd();

        $this->get('/effyads/track/view/ad-1')->assertOk();

        Bus::assertDispatchedAfterResponse(RecordTrackingEvent::class);
    }

    public function test_explicit_queue_connection_queues_the_event(): void
    {
        config(['effyads.queue_connection' => 'redis']);
        Bus::fake();
        $this->createAd();

        $this->get('/effyads/track/view/ad-1')->assertOk();

        Bus::assertDispatched(RecordTrackingEvent::class, fn ($job) => $job->connection === 'redis');
        Bus::assertNotDispatchedAfterResponse(RecordTrackingEvent::class);
    }

    public function test_job_retries_on_a_real_queue(): void
    {
        $job = new RecordTrackingEvent('ad-1', 'view', '1.1.1.1', 'UA', null, 'https://site.test');

        $this->assertSame(3, $job->tries);
        $this->assertNotEmpty($job->backoff());
    }
}

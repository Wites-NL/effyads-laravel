<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Jobs\RecordTrackingEvent;
use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class TrackingAbuseTest extends TestCase
{
    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        Cache::flush();
    }

    private function pixel(string $id, string $ua = self::BROWSER, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get("/effyads/track/view/{$id}", ['User-Agent' => $ua]);
    }

    public function test_view_of_unknown_ad_returns_gif_but_records_nothing(): void
    {
        $this->pixel('missing')->assertOk()->assertHeader('Content-Type', 'image/gif');

        Bus::assertNotDispatched(RecordTrackingEvent::class);
    }

    public function test_view_of_inactive_ad_records_nothing(): void
    {
        $this->createAd(['status' => 'paused']);

        $this->pixel('ad-1')->assertOk();

        Bus::assertNotDispatched(RecordTrackingEvent::class);
    }

    public function test_click_on_inactive_ad_redirects_without_recording(): void
    {
        $this->createAd(['status' => 'paused']);

        $this->get('/effyads/track/click/ad-1', ['User-Agent' => self::BROWSER])->assertRedirect('https://advertiser.test/landing');

        Bus::assertNotDispatched(RecordTrackingEvent::class);
    }

    public function test_repeated_views_by_the_same_visitor_are_counted_once(): void
    {
        $this->createAd();

        $this->pixel('ad-1');
        $this->pixel('ad-1');
        $this->pixel('ad-1', ip: '10.0.0.2');

        Bus::assertDispatchedTimes(RecordTrackingEvent::class, 2);
    }

    public function test_view_and_click_are_deduplicated_separately(): void
    {
        $this->createAd();

        $this->pixel('ad-1');
        $this->get('/effyads/track/click/ad-1', ['User-Agent' => self::BROWSER]);
        $this->get('/effyads/track/click/ad-1', ['User-Agent' => self::BROWSER]);

        Bus::assertDispatchedTimes(RecordTrackingEvent::class, 2);
    }

    public function test_dedupe_window_is_configurable(): void
    {
        config(['effyads.tracking.dedupe_seconds.view' => 0]);
        $this->createAd();

        $this->pixel('ad-1');
        $this->pixel('ad-1');

        Bus::assertDispatchedTimes(RecordTrackingEvent::class, 2);
    }

    public function test_bots_and_empty_user_agents_are_not_counted(): void
    {
        $this->createAd();

        $this->pixel('ad-1', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->assertOk();
        $this->pixel('ad-1', '')->assertOk();

        Bus::assertNotDispatched(RecordTrackingEvent::class);
    }

    public function test_tracking_routes_are_rate_limited_per_ip(): void
    {
        config(['effyads.tracking.rate_limit' => 3]);

        foreach (range(1, 3) as $i) {
            $this->pixel('missing')->assertOk();
        }

        $this->pixel('missing')->assertStatus(429);
        $this->pixel('missing', ip: '10.0.0.9')->assertOk();
    }
}

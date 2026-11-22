<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
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
}

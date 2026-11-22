<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;

class AdComponentTest extends TestCase
{
    public function test_renders_an_ad_for_a_matching_zone(): void
    {
        $this->createAd();

        $html = (string) $this->blade('<x-effyads::ad zone="homepage-hero" />');

        $this->assertStringContainsString('effyads-ad', $html);
        $this->assertStringContainsString('https://cdn.test/banner.png', $html);
        $this->assertStringContainsString('/effyads/track/click/ad-1', $html);
        $this->assertStringContainsString('/effyads/track/view/ad-1', $html);
    }

    public function test_renders_nothing_for_another_zone(): void
    {
        $this->createAd();

        $html = trim((string) $this->blade('<x-effyads::ad zone="sidebar" />'));

        $this->assertSame('', $html);
    }

    public function test_renders_nothing_for_inactive_ads(): void
    {
        $this->createAd(['status' => 'paused']);

        $this->assertSame('', trim((string) $this->blade('<x-effyads::ad zone="homepage-hero" />')));
    }
}

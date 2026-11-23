<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Models\Advertisement;
use EffyAds\Laravel\Tests\TestCase;

class AdFormatTest extends TestCase
{
    public function test_store_saves_the_format_and_treats_an_empty_one_as_none(): void
    {
        $this->postJson('/effyads/v1/advertisements', $this->adPayload([
            'format' => 'medium-rectangle', 'media_width' => 300, 'media_height' => 250,
        ]), $this->apiHeaders())->assertCreated();

        $this->assertSame('medium-rectangle', Advertisement::first()->format);

        $this->postJson('/effyads/v1/advertisements', $this->adPayload(['format' => '']), $this->apiHeaders())->assertOk();

        $this->assertNull(Advertisement::first()->format);
    }

    public function test_store_accepts_formats_this_version_does_not_know(): void
    {
        $this->postJson('/effyads/v1/advertisements', $this->adPayload(['format' => 'future-format']), $this->apiHeaders())
            ->assertCreated();
    }

    public function test_a_format_slot_only_shows_ads_of_that_format(): void
    {
        $this->createAd(['platform_id' => 'rect', 'format' => 'medium-rectangle', 'media_url' => 'https://cdn.test/rect.png']);
        $this->createAd(['platform_id' => 'board', 'format' => 'leaderboard', 'media_url' => 'https://cdn.test/board.png']);
        $this->createAd(['platform_id' => 'any', 'media_url' => 'https://cdn.test/any.png']);

        for ($i = 0; $i < 10; $i++) {
            app(\EffyAds\Laravel\Support\ActiveAdvertisements::class)->flush();
            $html = (string) $this->blade('<x-effyads::ad zone="homepage-hero" format="medium-rectangle" />');
            $this->assertStringContainsString('https://cdn.test/rect.png', $html);
            $this->assertStringContainsString('effyads-format-medium-rectangle', $html);
            $this->assertStringContainsString('width="300"', $html);
            $this->assertStringContainsString('height="250"', $html);
        }
    }

    public function test_a_format_slot_without_matching_ads_renders_nothing(): void
    {
        $this->createAd(['format' => 'leaderboard']);

        $this->assertSame('', trim((string) $this->blade('<x-effyads::ad zone="homepage-hero" format="half-page" />')));
    }

    public function test_a_slot_without_format_shows_any_ad(): void
    {
        $this->createAd(['format' => 'leaderboard']);

        $html = (string) $this->blade('<x-effyads::ad zone="homepage-hero" />');

        $this->assertStringContainsString('https://cdn.test/banner.png', $html);
        $this->assertStringContainsString('width="728"', $html);
    }
}

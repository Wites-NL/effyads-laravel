<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Models\Advertisement;
use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdComponentBehaviourTest extends TestCase
{
    private function render(string $blade): string
    {
        return trim((string) $this->blade($blade));
    }

    public function test_scheduled_ad_with_passed_start_date_is_shown(): void
    {
        $this->createAd(['status' => 'scheduled', 'start_date' => now()->subHour()]);

        $this->assertStringContainsString('effyads-ad', $this->render('<x-effyads::ad zone="homepage-hero" />'));
        $this->assertTrue(Advertisement::first()->isCurrentlyActive());
    }

    public function test_scheduled_ad_with_future_start_date_is_not_shown(): void
    {
        $this->createAd(['status' => 'scheduled', 'start_date' => now()->addHour()]);

        $this->assertSame('', $this->render('<x-effyads::ad zone="homepage-hero" />'));
        $this->assertFalse(Advertisement::first()->isCurrentlyActive());
    }

    public function test_scheduled_ad_without_start_date_is_not_shown(): void
    {
        $this->createAd(['status' => 'scheduled']);

        $this->assertSame('', $this->render('<x-effyads::ad zone="homepage-hero" />'));
    }

    public function test_expired_ad_is_not_shown(): void
    {
        $this->createAd(['end_date' => now()->subMinute()]);

        $this->assertSame('', $this->render('<x-effyads::ad zone="homepage-hero" />'));
        $this->assertFalse(Advertisement::first()->isCurrentlyActive());
    }

    public function test_two_zones_on_one_page_cost_one_query(): void
    {
        $this->createAd(['platform_id' => 'hero']);
        $this->createAd(['platform_id' => 'side', 'targeting' => ['zones' => ['include' => ['sidebar']]]]);

        DB::enableQueryLog();
        $html = $this->render('<x-effyads::ad zone="homepage-hero" /><x-effyads::ad zone="sidebar" />');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertStringContainsString('/effyads/track/view/hero', $html);
        $this->assertStringContainsString('/effyads/track/view/side', $html);
        $this->assertCount(1, $queries);
    }

    public function test_rotation_first_always_shows_the_first_match(): void
    {
        config(['effyads.rotation' => 'first']);
        $this->createAd(['platform_id' => 'one']);
        $this->createAd(['platform_id' => 'two']);

        foreach (range(1, 5) as $i) {
            $this->assertStringContainsString('/view/one', $this->render('<x-effyads::ad zone="homepage-hero" />'));
        }
    }

    public function test_random_rotation_shows_every_match(): void
    {
        $this->createAd(['platform_id' => 'one']);
        $this->createAd(['platform_id' => 'two']);

        $seen = [];
        foreach (range(1, 60) as $i) {
            $html = $this->render('<x-effyads::ad zone="homepage-hero" />');
            $seen[str_contains($html, '/view/one') ? 'one' : 'two'] = true;
        }

        $this->assertCount(2, $seen);
    }

    public function test_weight_zero_is_never_shown(): void
    {
        $this->createAd(['platform_id' => 'one']);
        $this->createAd(['platform_id' => 'never', 'targeting' => ['weight' => 0, 'zones' => ['include' => ['homepage-hero']]]]);

        foreach (range(1, 30) as $i) {
            $this->assertStringContainsString('/view/one', $this->render('<x-effyads::ad zone="homepage-hero" />'));
        }
    }

    public function test_unknown_zone_logs_a_warning_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        Log::spy();

        $this->render('<x-effyads::ad zone="does-not-exist" />');

        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'does-not-exist'))->once();
    }

    public function test_unknown_zone_does_not_log_outside_debug_mode(): void
    {
        config(['app.debug' => false]);
        Log::spy();

        $this->render('<x-effyads::ad zone="does-not-exist" />');

        Log::shouldNotHaveReceived('warning');
    }

    public function test_class_attribute_lazy_loading_and_dimensions(): void
    {
        $this->createAd(['media_width' => 728, 'media_height' => 90]);

        $html = $this->render('<x-effyads::ad zone="homepage-hero" class="my-ad" />');

        $this->assertStringContainsString('class="effyads-ad my-ad"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('width="728"', $html);
        $this->assertStringContainsString('height="90"', $html);
    }

    public function test_dimensions_can_be_set_on_the_component(): void
    {
        $this->createAd();

        $html = $this->render('<x-effyads::ad zone="homepage-hero" :width="300" :height="250" />');

        $this->assertStringContainsString('width="300"', $html);
        $this->assertStringContainsString('height="250"', $html);
    }

    public function test_api_accepts_media_dimensions(): void
    {
        $this->postJson('/effyads/v1/advertisements', $this->adPayload(['media_width' => 300, 'media_height' => 250]), $this->apiHeaders())
            ->assertCreated();

        $this->assertSame(300, Advertisement::first()->media_width);
    }
}

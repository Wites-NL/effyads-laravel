<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Models\Advertisement;
use EffyAds\Laravel\Tests\TestCase;

class AdvertisementApiTest extends TestCase
{
    public function test_store_creates_an_advertisement(): void
    {
        $this->postJson('/effyads/v1/advertisements', $this->adPayload(), $this->apiHeaders())
            ->assertCreated()
            ->assertJson(['success' => true, 'platform_id' => 'ad-1']);

        $this->assertDatabaseHas('effyads_advertisements', ['platform_id' => 'ad-1', 'title' => 'Test ad']);
    }

    public function test_store_updates_an_existing_advertisement(): void
    {
        $this->createAd();

        $this->postJson('/effyads/v1/advertisements', $this->adPayload(['title' => 'Changed']), $this->apiHeaders())
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Advertisement updated successfully']);

        $this->assertSame(1, Advertisement::count());
        $this->assertSame('Changed', Advertisement::first()->title);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/effyads/v1/advertisements', ['platform_id' => 'x'], $this->apiHeaders())
            ->assertUnprocessable();
    }

    public function test_index_show_update_and_destroy(): void
    {
        $this->createAd();

        $this->getJson('/effyads/v1/advertisements', $this->apiHeaders())
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->getJson('/effyads/v1/advertisements/ad-1', $this->apiHeaders())
            ->assertOk()
            ->assertJson(['platform_id' => 'ad-1']);

        $this->getJson('/effyads/v1/advertisements/missing', $this->apiHeaders())->assertNotFound();

        $this->putJson('/effyads/v1/advertisements/ad-1', $this->adPayload(['title' => 'Updated']), $this->apiHeaders())
            ->assertOk();
        $this->assertSame('Updated', Advertisement::first()->title);

        $this->deleteJson('/effyads/v1/advertisements/ad-1', [], $this->apiHeaders())->assertOk();
        $this->assertSame(0, Advertisement::count());
    }

    public function test_bulk_sync_deletes_missing_advertisements(): void
    {
        $this->createAd(['platform_id' => 'keep']);
        $this->createAd(['platform_id' => 'drop']);

        $this->postJson('/effyads/v1/sync/bulk', ['platform_ids' => ['keep']], $this->apiHeaders())
            ->assertOk()
            ->assertJson(['deleted_count' => 1]);

        $this->assertSame(['keep'], Advertisement::pluck('platform_id')->all());
    }

    public function test_targeting_options_lists_zones(): void
    {
        $this->getJson('/effyads/v1/targeting/options', $this->apiHeaders())
            ->assertOk()
            ->assertJsonPath('data.zones.0.name', 'homepage-hero');
    }
}

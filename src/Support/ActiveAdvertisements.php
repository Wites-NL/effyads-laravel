<?php

namespace EffyAds\Laravel\Support;

use EffyAds\Laravel\Models\Advertisement;
use Illuminate\Support\Collection;

/**
 * Loads the currently active advertisements once per site per request, so
 * any number of <x-effyads::ad> components on a page cost a single query.
 * Bound as a scoped singleton: reset between requests (also under Octane).
 */
class ActiveAdvertisements
{
    /** @var array<string, Collection<int, Advertisement>> */
    private array $loaded = [];

    /**
     * @return Collection<int, Advertisement>
     */
    public function forSite(?string $site): Collection
    {
        return $this->loaded[Advertisement::siteColumnValue($site)] ??= Advertisement::forSite($site)
            ->currentlyActive()
            ->orderBy('id')
            ->get();
    }

    /**
     * Pick the ad to show in a zone, following config('effyads.rotation').
     */
    public function pick(?string $site, string $zone): ?Advertisement
    {
        $matches = $this->forSite($site)
            ->filter(fn (Advertisement $ad) => $ad->matchesZone($zone))
            ->values();

        if ($matches->isEmpty()) {
            return null;
        }

        if (config('effyads.rotation', 'random') === 'first') {
            return $matches->first();
        }

        return $this->weightedRandom($matches);
    }

    /**
     * @param  Collection<int, Advertisement>  $ads
     */
    private function weightedRandom(Collection $ads): ?Advertisement
    {
        $total = $ads->sum(fn (Advertisement $ad) => $ad->rotationWeight());

        if ($total <= 0) {
            return $ads->random();
        }

        $target = (mt_rand() / mt_getrandmax()) * $total;

        foreach ($ads as $ad) {
            $target -= $ad->rotationWeight();
            if ($target <= 0 && $ad->rotationWeight() > 0) {
                return $ad;
            }
        }

        return $ads->last(fn (Advertisement $ad) => $ad->rotationWeight() > 0);
    }

    public function flush(): void
    {
        $this->loaded = [];
    }
}

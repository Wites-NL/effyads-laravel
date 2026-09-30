<?php

namespace EffyAds\Laravel\View\Components;

use EffyAds\Laravel\Models\Advertisement;
use Illuminate\View\Component;
use Illuminate\View\View;

class EffyAd extends Component
{
    public ?Advertisement $ad;

    public function __construct(public string $zone)
    {
        $this->ad = Advertisement::where('status', 'active')
            ->get()
            ->first(fn (Advertisement $ad) => $ad->isCurrentlyActive() && $ad->matchesZone($zone));
    }

    public function render(): View
    {
        return view('effyads::components.ad');
    }
}

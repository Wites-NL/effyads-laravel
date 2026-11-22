<?php

namespace EffyAds\Laravel\View\Components;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Models\Advertisement;
use EffyAds\Laravel\Support\ActiveAdvertisements;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Illuminate\View\View;

class EffyAd extends Component
{
    public ?Advertisement $ad;

    public ?int $imageWidth;

    public ?int $imageHeight;

    public function __construct(
        public string $zone,
        ?int $width = null,
        ?int $height = null,
    ) {
        if (config('app.debug') && ! array_key_exists($zone, (array) config('effyads.zones', []))) {
            Log::warning("[EffyAds] Unknown zone \"{$zone}\": add it to 'zones' in config/effyads.php so it can be targeted in the dashboard.");
        }

        $this->ad = app(ActiveAdvertisements::class)->pick(EffyAds::site(), $zone);
        $this->imageWidth = $width ?? $this->ad?->media_width;
        $this->imageHeight = $height ?? $this->ad?->media_height;
    }

    public function render(): View
    {
        return view('effyads::components.ad');
    }
}

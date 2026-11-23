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
        public ?string $format = null,
    ) {
        if (config('app.debug') && ! array_key_exists($zone, (array) config('effyads.zones', []))) {
            Log::warning("[EffyAds] Unknown zone \"{$zone}\": add it to 'zones' in config/effyads.php so it can be targeted in the dashboard.");
        }

        if (config('app.debug') && $format !== null && ! array_key_exists($format, Advertisement::FORMATS)) {
            Log::warning("[EffyAds] Unknown format \"{$format}\": use one of ".implode(', ', array_keys(Advertisement::FORMATS)).'.');
        }

        $this->ad = app(ActiveAdvertisements::class)->pick(EffyAds::site(), $zone, $format);

        // Size from the attributes, else what the platform sent, else the format's standard size.
        $standard = Advertisement::FORMATS[$this->ad?->format ?? ''] ?? [null, null];
        $this->imageWidth = $width ?? $this->ad?->media_width ?? $standard[0];
        $this->imageHeight = $height ?? $this->ad?->media_height ?? $standard[1];
    }

    public function render(): View
    {
        return view('effyads::components.ad');
    }
}

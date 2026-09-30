<?php

namespace EffyAds\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecordTrackingEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $platformId,
        public string $eventType,
        public string $ipAddress,
        public string $userAgent,
        public ?string $referrer,
        public string $siteUrl,
    ) {
    }

    public function handle(): void
    {
        $platformUrl = config('effyads.platform_url');

        if (empty($platformUrl)) {
            Log::warning('[EffyAds] platform_url not configured, skipping tracking event');

            return;
        }

        $response = Http::timeout(5)->post(rtrim($platformUrl, '/').'/api/tracking', [
            'platform_id' => $this->platformId,
            'event_type' => $this->eventType,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'timestamp' => now()->toDateTimeString(),
            'site_url' => $this->siteUrl,
        ]);

        if ($response->failed()) {
            Log::warning('[EffyAds] tracking event failed', ['status' => $response->status()]);
        }
    }
}

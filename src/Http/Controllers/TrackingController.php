<?php

namespace EffyAds\Laravel\Http\Controllers;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Jobs\RecordTrackingEvent;
use EffyAds\Laravel\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class TrackingController extends Controller
{
    private const TRANSPARENT_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    private const DEFAULT_DEDUPE_SECONDS = ['view' => 1800, 'click' => 60];

    // GET /effyads/track/view/{platform_id} — 1x1 pixel, records a view for an active ad
    public function view(Request $request, string $platformId)
    {
        $ad = $this->find($request, $platformId);

        if ($ad && $ad->isCurrentlyActive()) {
            $this->recordEvent($request, $ad, 'view');
        }

        return response(base64_decode(self::TRANSPARENT_GIF), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    // GET /effyads/track/click/{platform_id} — records a click, then redirects to the ad's target URL
    public function click(Request $request, string $platformId)
    {
        $ad = $this->find($request, $platformId);

        abort_if($ad === null, 404);

        if ($ad->isCurrentlyActive()) {
            $this->recordEvent($request, $ad, 'click');
        }

        return redirect()->away($ad->link, 302);
    }

    private function find(Request $request, string $platformId): ?Advertisement
    {
        return Advertisement::forSite(EffyAds::site($request))->where('platform_id', $platformId)->first();
    }

    private function recordEvent(Request $request, Advertisement $ad, string $eventType): void
    {
        $userAgent = (string) $request->userAgent();

        if ($this->isBot($userAgent) || ! $this->isFirstInWindow($request, $ad, $eventType, $userAgent)) {
            return;
        }

        RecordTrackingEvent::dispatchForTracking(
            $ad->platform_id,
            $eventType,
            (string) $request->ip(),
            $userAgent,
            $request->headers->get('referer'),
            $request->getSchemeAndHttpHost(),
        );
    }

    private function isBot(string $userAgent): bool
    {
        if (trim($userAgent) === '') {
            return true;
        }

        $pattern = config('effyads.tracking.bot_user_agents');

        return is_string($pattern) && $pattern !== '' && preg_match($pattern, $userAgent) === 1;
    }

    /**
     * Count the same visitor once per ad and event type per window.
     */
    private function isFirstInWindow(Request $request, Advertisement $ad, string $eventType, string $userAgent): bool
    {
        $seconds = (int) config("effyads.tracking.dedupe_seconds.{$eventType}", self::DEFAULT_DEDUPE_SECONDS[$eventType]);

        if ($seconds <= 0) {
            return true;
        }

        $key = 'effyads:track:'.$eventType.':'.sha1(implode('|', [
            $ad->site, $ad->platform_id, $request->ip(), $userAgent,
        ]));

        return Cache::add($key, 1, $seconds);
    }
}

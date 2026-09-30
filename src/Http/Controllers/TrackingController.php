<?php

namespace EffyAds\Laravel\Http\Controllers;

use EffyAds\Laravel\Jobs\RecordTrackingEvent;
use EffyAds\Laravel\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TrackingController extends Controller
{
    private const TRANSPARENT_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    // GET /effyads/track/view/{platform_id} — 1x1 pixel, fires a view event
    public function view(Request $request, string $platformId)
    {
        $this->recordEvent($request, $platformId, 'view');

        return response(base64_decode(self::TRANSPARENT_GIF), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    // GET /effyads/track/click/{platform_id} — fires a click event, then redirects to the ad's target URL
    public function click(Request $request, string $platformId)
    {
        $ad = Advertisement::where('platform_id', $platformId)->firstOrFail();

        $this->recordEvent($request, $platformId, 'click');

        return redirect()->away($ad->link, 302);
    }

    private function recordEvent(Request $request, string $platformId, string $eventType): void
    {
        RecordTrackingEvent::dispatch(
            $platformId,
            $eventType,
            $request->ip(),
            (string) $request->userAgent(),
            $request->headers->get('referer'),
            $request->getSchemeAndHttpHost(),
        )->onConnection(config('effyads.queue_connection'));
    }
}

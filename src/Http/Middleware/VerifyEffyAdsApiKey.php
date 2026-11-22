<?php

namespace EffyAds\Laravel\Http\Middleware;

use Closure;
use EffyAds\Laravel\EffyAds;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyEffyAdsApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $provided = $request->header('X-EffyAds-API-Key');

        if (empty($provided) && config('effyads.allow_query_api_key') && $request->query('api_key')) {
            $provided = $request->query('api_key');
            Log::warning('[EffyAds] API key sent as ?api_key= query parameter. This is deprecated and will be removed: send the X-EffyAds-API-Key header instead.', [
                'path' => $request->path(),
            ]);
        }

        $stored = EffyAds::apiKey(EffyAds::site($request));

        // One generic answer for every failure, so the response gives no hint
        // whether the key was missing, wrong or not configured.
        if (! is_string($provided) || $provided === '' || $stored === null || ! hash_equals($stored, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return $next($request);
    }
}

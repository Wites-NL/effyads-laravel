<?php

namespace EffyAds\Laravel\Http\Middleware;

use Closure;
use EffyAds\Laravel\EffyAds;
use Illuminate\Http\Request;

class VerifyEffyAdsApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $provided = $request->header('X-EffyAds-API-Key') ?: $request->query('api_key');
        $stored = EffyAds::apiKey(EffyAds::site($request));

        if (empty($provided) || empty($stored) || ! hash_equals((string) $stored, (string) $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 403);
        }

        return $next($request);
    }
}

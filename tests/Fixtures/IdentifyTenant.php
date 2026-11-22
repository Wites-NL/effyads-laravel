<?php

namespace EffyAds\Laravel\Tests\Fixtures;

use Closure;
use Illuminate\Http\Request;

/** Stands in for a tenancy package's "initialize tenancy" middleware. */
class IdentifyTenant
{
    public static ?string $current = null;

    public function handle(Request $request, Closure $next)
    {
        static::$current = $request->header('X-Tenant');

        return $next($request);
    }
}

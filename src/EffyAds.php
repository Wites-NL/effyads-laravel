<?php

namespace EffyAds\Laravel;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Entry point for configuring the package at runtime.
 *
 * By default the package serves a single site with the key from
 * config('effyads.api_key'). A multi-tenant app registers two resolvers
 * (e.g. in AppServiceProvider::boot()):
 *
 *     EffyAds::resolveSiteUsing(fn (Request $request) => tenant()?->getTenantKey());
 *     EffyAds::resolveApiKeyUsing(fn (?string $site) => Tenant::find($site)?->effyads_api_key);
 *
 * Every advertisement, API call, Blade component and tracking event is then
 * scoped to the site the resolver returns for the current request.
 */
class EffyAds
{
    /** @var (Closure(Request): ?string)|null */
    protected static ?Closure $siteResolver = null;

    /** @var (Closure(?string): ?string)|null */
    protected static ?Closure $apiKeyResolver = null;

    /**
     * @param  (callable(Request): ?string)|null  $resolver
     */
    public static function resolveSiteUsing(?callable $resolver): void
    {
        static::$siteResolver = $resolver === null ? null : Closure::fromCallable($resolver);
    }

    /**
     * @param  (callable(?string): ?string)|null  $resolver
     */
    public static function resolveApiKeyUsing(?callable $resolver): void
    {
        static::$apiKeyResolver = $resolver === null ? null : Closure::fromCallable($resolver);
    }

    /**
     * The site the given (or current) request belongs to, or null for a
     * single-site install.
     */
    public static function site(?Request $request = null): ?string
    {
        if (static::$siteResolver === null) {
            return null;
        }

        $request ??= app('request');

        $site = (static::$siteResolver)($request);

        return $site === null || $site === '' ? null : (string) $site;
    }

    /**
     * The API key the platform must send for the given site.
     */
    public static function apiKey(?string $site): ?string
    {
        if (static::$apiKeyResolver !== null) {
            $key = (static::$apiKeyResolver)($site);

            return $key === null || $key === '' ? null : (string) $key;
        }

        $key = config('effyads.api_key');

        return empty($key) ? null : (string) $key;
    }

    public static function generateApiKey(): string
    {
        return Str::random(64);
    }

    /**
     * Reset both resolvers to the single-site defaults.
     */
    public static function flushResolvers(): void
    {
        static::$siteResolver = null;
        static::$apiKeyResolver = null;
    }
}

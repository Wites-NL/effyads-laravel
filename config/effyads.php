<?php

return [

    // Shared secret verified against the X-EffyAds-API-Key header on every
    // effyads/v1/* request. Generate one via `php artisan effyads:install`
    // and paste it into the EffyAds dashboard.
    'api_key' => env('EFFYADS_API_KEY'),

    // Temporarily also accept the key as ?api_key= in the URL. Off by default:
    // keys in URLs end up in access and proxy logs. Logs a deprecation
    // warning whenever it is used. Will be removed in a future version.
    'allow_query_api_key' => env('EFFYADS_ALLOW_QUERY_API_KEY', false),

    // Requests per minute per IP allowed on the effyads/v1 API.
    'api_rate_limit' => env('EFFYADS_API_RATE_LIMIT', 120),

    // Base URL of the EffyAds platform. Tracking events (view/click) are
    // POSTed to "{platform_url}/api/tracking".
    'platform_url' => env('EFFYADS_PLATFORM_URL', 'https://app.effyads.com'),

    // Queue connection for tracking events (views/clicks reported to the
    // platform). The default "sync" sends them right after the response has
    // been delivered to the visitor, so it needs no queue worker and never
    // slows down the pixel or click redirect. Only set this to a real queue
    // connection (e.g. "redis", "database") if a worker is running for it,
    // otherwise events pile up in the queue unsent.
    'queue_connection' => env('EFFYADS_QUEUE_CONNECTION', 'sync'),

    // Extra middleware for all EffyAds routes (API and tracking). Multi-tenant
    // apps add their tenant identification middleware here so the site
    // resolver (EffyAds::resolveSiteUsing) sees the current tenant, e.g.
    // [\Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class].
    'route_middleware' => [],

    // Disk (from config/filesystems.php) that uploaded ad media is stored on.
    'media_disk' => env('EFFYADS_MEDIA_DISK', 'public'),

    // Maximum size of an uploaded ad image, in kilobytes.
    'media_max_kb' => env('EFFYADS_MEDIA_MAX_KB', 5120),

    // Protection against inflated view and click counts.
    'tracking' => [
        // Requests per minute per IP on the public tracking routes.
        'rate_limit' => env('EFFYADS_TRACKING_RATE_LIMIT', 60),

        // The same visitor (IP + user agent) is counted once per ad per window.
        'dedupe_seconds' => [
            'view' => 1800,
            'click' => 60,
        ],

        // User agents matching this regex (and empty user agents) are not counted.
        // Set to null to count everything.
        'bot_user_agents' => '/bot|crawl|spider|slurp|mediapartners|facebookexternalhit|embedly|preview|headless|lighthouse|pingdom|uptime|monitor|curl|wget|python-requests|go-http-client|httpclient|java\//i',
    ],

    // Which ad a zone shows when several match: "random" picks one at random
    // on every render (weighted by targeting.weight, default 1), "first"
    // always shows the oldest matching ad.
    'rotation' => env('EFFYADS_ROTATION', 'random'),

    // Named placement slots the <x-effyads::ad zone="..." /> component and the
    // targeting/options endpoint expose to the platform. WordPress targets by
    // taxonomy/post-type/page; a plain Laravel app has no such universal
    // concept, so ad placement is targeted by these app-defined zones instead.
    'zones' => [
        // 'homepage-hero' => 'Homepage hero banner',
        // 'blog-sidebar'  => 'Blog sidebar',
    ],

];

<?php

return [

    // Shared secret verified against the X-EffyAds-API-Key header (or ?api_key=
    // query fallback) on every effyads/v1/* request. Generate one via
    // `php artisan effyads:install` and paste it into the EffyAds dashboard.
    'api_key' => env('EFFYADS_API_KEY'),

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

    // Named placement slots the <x-effyads::ad zone="..." /> component and the
    // targeting/options endpoint expose to the platform. WordPress targets by
    // taxonomy/post-type/page; a plain Laravel app has no such universal
    // concept, so ad placement is targeted by these app-defined zones instead.
    'zones' => [
        // 'homepage-hero' => 'Homepage hero banner',
        // 'blog-sidebar'  => 'Blog sidebar',
    ],

];

<?php

return [

    // Shared secret verified against the X-EffyAds-API-Key header (or ?api_key=
    // query fallback) on every effyads/v1/* request. Generate one via
    // `php artisan effyads:install` and paste it into the EffyAds dashboard.
    'api_key' => env('EFFYADS_API_KEY'),

    // Base URL of the EffyAds platform. Tracking events (view/click) are
    // POSTed to "{platform_url}/api/tracking".
    'platform_url' => env('EFFYADS_PLATFORM_URL', 'https://effyads.com'),

    // Queue connection used to dispatch tracking events asynchronously so a
    // slow platform response never blocks the pixel/redirect request.
    // Use "sync" to send inline instead (not recommended in production).
    'queue_connection' => env('EFFYADS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),

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

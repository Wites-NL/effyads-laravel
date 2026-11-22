<?php

use EffyAds\Laravel\Http\Controllers\TrackingController;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

// Public endpoints: hit directly by visitor browsers via the tracking pixel /
// click-through link rendered by <x-effyads::ad>, so no API key middleware.
Route::middleware([
    ...config('effyads.route_middleware', []),
    ThrottleRequests::class.':effyads-tracking',
])->group(function () {
    Route::get('effyads/track/view/{platform_id}', [TrackingController::class, 'view'])
        ->name('effyads.track.view');

    Route::get('effyads/track/click/{platform_id}', [TrackingController::class, 'click'])
        ->name('effyads.track.click');
});

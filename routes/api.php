<?php

use EffyAds\Laravel\Http\Controllers\AdvertisementController;
use EffyAds\Laravel\Http\Controllers\MediaController;
use EffyAds\Laravel\Http\Controllers\TargetingController;
use EffyAds\Laravel\Http\Middleware\VerifyEffyAdsApiKey;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('effyads/v1')
    ->middleware([
        ...config('effyads.route_middleware', []),
        ThrottleRequests::class.':effyads-api',
        VerifyEffyAdsApiKey::class,
    ])
    ->group(function () {
        Route::get('advertisements', [AdvertisementController::class, 'index']);
        Route::post('advertisements', [AdvertisementController::class, 'store']);
        Route::get('advertisements/{platform_id}', [AdvertisementController::class, 'show']);
        Route::put('advertisements/{platform_id}', [AdvertisementController::class, 'update']);
        Route::delete('advertisements/{platform_id}', [AdvertisementController::class, 'destroy']);

        Route::get('sync/status', [AdvertisementController::class, 'syncStatus']);
        Route::post('sync/bulk', [AdvertisementController::class, 'bulkSync']);

        Route::get('targeting/options', [TargetingController::class, 'options']);

        Route::post('media/upload', [MediaController::class, 'upload']);
    });

<?php

use Illuminate\Support\Facades\Route;
use Laraditz\TikTok\Http\Controllers\SellerController;
use Laraditz\TikTok\Http\Controllers\WebhookController;

Route::prefix('seller')->name('seller.')->group(function () {
    Route::get('/authorized', [SellerController::class, 'authorized'])->name('authorized');
});

Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::match(['get', 'post'], '/{event}', [WebhookController::class, 'event'])->name('event');
});


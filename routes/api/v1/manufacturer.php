<?php

use App\Http\Controllers\Api\V1\Admin\CertificateTypeController;
use App\Http\Controllers\Api\V1\Manufacturer\CertificationController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerCatalogController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProductController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProfileController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerRfqController;
use App\Http\Controllers\Api\V1\Manufacturer\OrderController;
use App\Http\Controllers\Api\V1\Manufacturer\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->group(function () {

    Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
        Route::get('/', 'show');
        Route::post('/subscribe', 'subscribe');
        Route::post('/cancel', 'cancel');
        Route::post('/upgrade', 'upgrade');
    });

    Route::prefix('/profile')->controller(ManufacturerProfileController::class)->group(function () {
        Route::get('/', 'index');
        // For Compnay profile
        Route::put('/update', 'update');
        // Basic Account Profile
        Route::put('/basic-profile', 'updateProfile');

        Route::put('/change/password', 'changePassword');
        Route::patch('/toggle-status', 'toggleStatus');
        Route::put('/notification-preferences', 'updateNotificationPreferences');
    });

    // Products Route

    Route::controller(ManufacturerProductController::class)->prefix('products')->group(function () {
        Route::get('/stats', 'stats');
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{product_id}', 'show');
        Route::put('/{product_id}', 'update');
        Route::delete('/{product_id}', 'destroy');
        Route::patch('/{product_id}/change-status', 'changeStatus');
        Route::patch('/{product_id}/duplicate-to-draft', 'duplicateToDraft');
    });

    Route::controller(ManufacturerCatalogController::class)->prefix('catalogs')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/stats', 'stats');
        Route::get('/{catalog_id}', 'show');
        Route::put('/{catalog_id}', 'update');
        Route::patch('/{catalog_id}/change-status', 'changeStatus');
        Route::delete('/{catalog_id}', 'destroy');
    });

    Route::controller(ManufacturerRfqController::class)->prefix('rfqs')->name('rfqs.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/counts', 'counts')->name('counts');
        Route::get('/{rfq}', 'show')->name('show');
        Route::post('/{rfq}/reply', 'reply')->name('reply');
        Route::post('/{rfq}/quote', 'sendQuote')->name('send-quote');
    });

    Route::controller(OrderController::class)->prefix('orders')->group(function (): void {
        Route::get('/select/products', 'selectProducts');
        Route::get('/select/buyers', 'selectBuyers');
        Route::get('/status-options', 'statusOptions');
        Route::get('/stats', 'stats');
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::post('/{order}/status-updates', 'storeStatusUpdate');
        Route::get('/{order}', 'show');
    });

    Route::controller(CertificateTypeController::class)->prefix('certificate/types')->group(function () {
        Route::get('/', 'index');
    });
    Route::controller(CertificationController::class)->prefix('certificate')->group(function () {
        Route::get('/stats', 'stats');
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{certificatId}', 'show');
        Route::put('/{certificatId}', 'update');
        Route::delete('/{certificateId}', 'destroy');

    });
});

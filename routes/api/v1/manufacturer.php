<?php

use App\Http\Controllers\Api\V1\Admin\CertificateTypeController;
use App\Http\Controllers\Api\V1\Manufacturer\CertificationController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerAnalyticsController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerCatalogController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerDashboardController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerExportMarketsController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProductController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerProfileController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerReviewCenterController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerRfqController;
use App\Http\Controllers\Api\V1\Manufacturer\OrderController;
use App\Http\Controllers\Api\V1\Manufacturer\ManufacturerPromotionController;
use App\Http\Controllers\Api\V1\Manufacturer\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('')->group(function () {

    Route::prefix('subscriptions')->controller(SubscriptionController::class)->group(function () {
        Route::get('/', 'show');
        Route::post('/subscribe', 'subscribe');
        Route::post('/cancel', 'cancel');
        Route::post('/upgrade', 'upgrade');
    });

    Route::get('/review-center', [ManufacturerReviewCenterController::class, 'show']);

    Route::prefix('promotions')->controller(ManufacturerPromotionController::class)->group(function (): void {
        Route::get('/active', 'active');
        Route::get('/my-application', 'myApplication');
        Route::post('/apply', 'apply');
    });

    Route::middleware(['subscription.active'])->group(function () {

        Route::get('/dashboard', [ManufacturerDashboardController::class, 'overview'])
            ->middleware('plan.feature:basic_analytics|advanced_analytics');

        Route::prefix('analytics')->controller(ManufacturerAnalyticsController::class)->group(function (): void {
            Route::get('/metrics', 'metrics')->middleware('plan.feature:basic_analytics|advanced_analytics');
            Route::get('/products', 'products')->middleware('plan.feature:basic_analytics|advanced_analytics');
            Route::get('/performance', 'performance')->middleware('plan.feature:advanced_analytics');
            Route::get('/countries', 'countries')->middleware('plan.feature:advanced_analytics');
            Route::get('/funnel', 'funnel')->middleware('plan.feature:advanced_analytics');
        });

        Route::prefix('markets')->controller(ManufacturerExportMarketsController::class)
            ->middleware('plan.feature:export_markets_section')
            ->group(function (): void {
                Route::get('/', 'index');
                Route::get('/countries', 'countries');
                Route::post('/regions', 'storeRegion');
                Route::put('/regions/{market}', 'updateRegion');
                Route::delete('/regions/{market}', 'destroyRegion');
                Route::put('/countries/sync', 'syncCountries');
            });

        Route::prefix('/profile')->controller(ManufacturerProfileController::class)->group(function () {
            Route::get('/', 'index');
            Route::put('/basic-profile', 'updateProfile');
            Route::put('/change/password', 'changePassword');
            Route::patch('/toggle-status', 'toggleStatus');
            Route::put('/notification-preferences', 'updateNotificationPreferences');
            Route::put('/update', 'update')->middleware('plan.feature:company_profile');
        });

        Route::controller(ManufacturerProductController::class)->prefix('products')->group(function () {
            Route::get('/stats', 'stats')->middleware('plan.feature:basic_analytics|advanced_analytics');
            Route::get('/', 'index');
            Route::post('/', 'store')->middleware('plan.feature:product_limit');
            Route::get('/{product_id}', 'show');
            Route::put('/{product_id}', 'update');
            Route::delete('/{product_id}', 'destroy');
            Route::patch('/{product_id}/change-status', 'changeStatus');
            Route::patch('/{product_id}/duplicate-to-draft', 'duplicateToDraft')
                ->middleware('plan.feature:product_limit');
        });

        Route::controller(ManufacturerCatalogController::class)->prefix('catalogs')->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store')->middleware('plan.feature:catalog_upload');
            Route::get('/stats', 'stats');
            Route::get('/{catalog_id}', 'show');
            Route::put('/{catalog_id}', 'update')->middleware('plan.feature:catalog_upload');
            Route::patch('/{catalog_id}/change-status', 'changeStatus')->middleware('plan.feature:catalog_upload');
            Route::delete('/{catalog_id}', 'destroy')->middleware('plan.feature:catalog_upload');
        });

        Route::controller(ManufacturerRfqController::class)->prefix('rfqs')->name('rfqs.')->middleware('plan.feature:inquiry_rfq_inbox')->group(function () {
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

        Route::controller(CertificateTypeController::class)->prefix('certificate/types')->middleware('plan.feature:certifications_section')->group(function () {
            Route::get('/', 'index');
        });

        Route::controller(CertificationController::class)->prefix('certificate')->middleware('plan.feature:certifications_section')->group(function () {
            Route::get('/stats', 'stats');
            Route::get('/', 'index');
            Route::post('/create', 'store');
            Route::get('/{certificatId}', 'show');
            Route::put('/{certificatId}', 'update');
            Route::delete('/{certificateId}', 'destroy');
        });
    });
});

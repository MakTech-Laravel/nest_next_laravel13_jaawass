<?php

use App\Http\Controllers\Api\V1\Buyer\BuyerDashboardController;
use App\Http\Controllers\Api\V1\Buyer\BuyerProductController;
use App\Http\Controllers\Api\V1\Buyer\BuyerProfileController;
use App\Http\Controllers\Api\V1\Buyer\BuyerRfqController;
use App\Http\Controllers\Api\V1\Buyer\BuyerSupplierController;
use App\Http\Controllers\Api\V1\Buyer\OrderController;
use App\Http\Controllers\Api\V1\Buyer\ProductReviewController;
use App\Http\Controllers\Api\V1\Buyer\SupplierReportController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [BuyerDashboardController::class, 'overview']);
Route::get('/dashboard/activity', [BuyerDashboardController::class, 'activity']);

Route::prefix('profile')->controller(BuyerProfileController::class)->group(function () {
    Route::get('/', 'show');
    Route::put('/update', 'update');
    Route::delete('/delete', 'destroy');
    Route::put('/change-password', 'changePassword');
    Route::patch('/toggle-status', 'toggleStatus');
    Route::put('/notification-preferences', 'updateNotificationPreferences');
});

Route::controller(BuyerRfqController::class)->prefix('rfqs')->name('rfqs.')->group(function () {
    Route::get('/', 'index');
    Route::get('/search', 'search');
    Route::get('/counts', 'counts');
    Route::get('/{rfq}', 'show');
    Route::post('/', 'store');
    Route::patch('/{rfq}/status', 'updateStatus');
    Route::post('/{rfq}/respond-quote', 'respondToQuote')->name('respond-quote');
});

Route::controller(OrderController::class)->prefix('orders')->group(function (): void {
    Route::get('/stats', 'stats');
    Route::get('/', 'index');
    Route::get('/{order}', 'show');
});

Route::controller(BuyerProductController::class)->prefix('products')->group(function () {
    Route::get('/saved', 'indexSaved');
    Route::post('/saved', 'saveProduct');
    Route::delete('/saved/{product}', 'unsaveProduct');

    Route::get('/compare', 'indexCompare');
    Route::post('/compare', 'addToCompare');
    Route::delete('/compare/{product}', 'removeFromCompare');
});

Route::controller(ProductReviewController::class)->prefix('products')->group(function () {
    Route::post('/{product}/reviews', 'store');
});

Route::controller(BuyerSupplierController::class)->prefix('suppliers')->group(function () {
    Route::get('/saved', 'indexSaved');
    Route::post('/saved', 'saveSupplier');
    Route::delete('/saved/{supplier}', 'unsaveSupplier');

    Route::get('/compare', 'indexCompare');
    Route::post('/compare', 'addToCompare');
    Route::delete('/compare/{supplier}', 'removeFromCompare');
});

Route::controller(SupplierReportController::class)->prefix('suppliers')->group(function (): void {
    Route::get('/{manufacturer}/reports/can-report', 'canReport');
    Route::post('/{manufacturer}/reports', 'store');
});

Route::controller(SupplierReportController::class)->prefix('supplier-reports')->group(function (): void {
    Route::get('/', 'index');
});

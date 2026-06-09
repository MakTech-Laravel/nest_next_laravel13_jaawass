<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryAndSubCategoryController;
use App\Http\Controllers\Api\V1\Admin\ArticleController;
use App\Http\Controllers\Api\V1\Admin\FaqCategoryController;
use App\Http\Controllers\Api\V1\Admin\FaqController;
use App\Http\Controllers\Api\V1\Admin\HelpCenterArticleController;
use App\Http\Controllers\Api\V1\Admin\HelpCenterCategoryController;
use App\Http\Controllers\Api\V1\Admin\PlanController;
use App\Http\Controllers\Api\V1\Admin\ShippinMethodController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CurrencyController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\QuickFilterController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use Illuminate\Support\Facades\Route;

// Route::post('/register', [AuthController::class, 'register'])
//     ->middleware(['throttle:api-register']);

// Route::post('/login', [AuthController::class, 'login'])
//     ->middleware(['throttle:api-login']);

// Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
//     ->middleware(['throttle:api-password-reset']);

// Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
//     ->middleware(['signed', 'throttle:6,1'])
//     ->name('verification.verify');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->middleware(['throttle:api-register']);
    Route::post('/login', 'login')->middleware(['throttle:api-login']);
    Route::post('/two-factor-challenge', 'twoFactorChallenge')->middleware(['throttle:api-login']);
    Route::post('/forgot-password', 'forgotPassword')->middleware(['throttle:api-password-reset']);
    Route::post('/reset-password', 'resetPassword')->middleware(['throttle:api-password-reset-verify']);
});

Route::controller(SocialAuthController::class)->prefix('auth')->group(function () {

    // Redirect flow (fallback — mainly for browser-based testing)
    Route::get('/{provider}/redirect', 'redirectUrl');
    Route::get('/{provider}/callback', 'handleCallback');

    // Token flow — primary flow for Next.js / mobile apps
    Route::post('/google/token', 'googleTokenLogin');
    Route::post('/facebook/token', 'facebookTokenLogin');

    // Profile completion — called after receiving a setup_token
    Route::post('/social/complete-profile', 'completeProfile');
});

Route::controller(AccountController::class)->prefix('account')->group(function () {
    Route::post('/restore-delete/request', 'requestRestoreOtp')->middleware(['throttle:account-restore-otp-request']);
    Route::post('/restore-delete/verify', 'verifyRestoreOtp')->middleware(['throttle:account-restore-otp-verify']);
});

Route::get('/currencies', [CurrencyController::class, 'index']);

Route::get('/quick-filters', [QuickFilterController::class, 'index']);

// Get FAQ categories without Auth
Route::controller(FaqCategoryController::class)->prefix('faqs')->group(function () {
    Route::get('/categories', 'index');
});

// Click Faq and Store Information
Route::controller(FaqController::class)->prefix('faqs')->group(function () {
    Route::patch('/{faq}/click', 'clicked');
});

// Get Plans without Auth
Route::controller(PlanController::class)->prefix('plans')->group(function () {
    Route::get('/', 'index');
});

// Categories and Sub Categories

Route::controller(AdminCategoryAndSubCategoryController::class)->prefix('categories')->group(function () {
    Route::get('/', 'index');
});
Route::controller(ShippinMethodController::class)->prefix('shipping/methods')->group(function () {
    Route::get('/', 'getActiveShippingMethods');
});

Route::controller(ProductController::class)->prefix('products')->group(function () {
    Route::get('/', 'index');
    Route::get('/category/{categoryId}', 'byCategory');
    Route::get('/sub-category/{subCategoryId}', 'bySubCategory');
    Route::post('/', 'store');
    Route::get('/{product}', 'show');
    Route::put('/{product}', 'update');
    Route::delete('/{product}', 'destroy');
});

Route::controller(ArticleController::class)->prefix('articles')->group(function () {
    Route::get('/', 'index');
    Route::get('/{article}', 'show');
});

Route::controller(HelpCenterCategoryController::class)->prefix('help-center-categories')->group(function () {
    Route::get('', 'index');
    Route::get('/{helpCenterCategory}', 'show');
});

Route::controller(HelpCenterArticleController::class)->prefix('help-center-articles')->group(function () {
    Route::get('/', 'index');
    Route::get('/{helpCenterArticle}', 'showArticleInFrontend');
    Route::patch('/{helpCenterArticle}/is-helpful', 'articleHelpful');
});
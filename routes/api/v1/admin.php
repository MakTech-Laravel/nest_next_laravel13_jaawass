<?php

use App\Http\Controllers\Api\V1\Admin\AdminCategoryAndSubCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\ArticleCategoryController;
use App\Http\Controllers\Api\V1\Admin\ArticleController;
use App\Http\Controllers\Api\V1\Admin\CertificateTypeController;
use App\Http\Controllers\Api\V1\Admin\CertificationController;
use App\Http\Controllers\Api\V1\Admin\ContactController;
use App\Http\Controllers\Api\V1\Admin\CurrencyAdminController;
use App\Http\Controllers\Api\V1\Admin\FaqCategoryController;
use App\Http\Controllers\Api\V1\Admin\FaqController;
use App\Http\Controllers\Api\V1\Admin\FeatureController;
use App\Http\Controllers\Api\V1\Admin\HelpCenterArticleController;
use App\Http\Controllers\Api\V1\Admin\HelpCenterCategoryController;
use App\Http\Controllers\Api\V1\Admin\ManufacturerController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\PlanController;
use App\Http\Controllers\Api\V1\Admin\Product\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\PromotionController;
use App\Http\Controllers\Api\V1\Admin\QuickFilterAdminController;
use App\Http\Controllers\Api\V1\Admin\RfqSubmissionAdminController;
use App\Http\Controllers\Api\V1\Admin\ShippinMethodController;
use App\Http\Controllers\Api\V1\Admin\TicketAdminController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [AdminDashboardController::class, 'overview']);

Route::controller(UserController::class)->prefix('users')->group(function (): void {
    Route::get('/', 'index');

    Route::get('/{user}', 'show');
    Route::get('/{user}/login-histories', 'loginHistories');
    Route::patch('/{user}/deactivate', 'deactivate');
    Route::patch('/{user}/reactivate', 'reactivate');
    Route::patch('/{user}/suspend', 'suspend');
    // Route::patch('/{user}/unsuspend', 'unsuspend');
    Route::patch('/{user}/manufacture-status', 'updateManufactureStatus');
    Route::delete('/{user}', 'destroy');
    Route::patch('/{user}/active', 'activate');
});

Route::controller(ManufacturerController::class)->prefix('manufacturer')->group(function (): void {
    Route::get('/', 'index');
    Route::post('/create', 'store');
    Route::get('/{manufacturer}', 'show');
    Route::delete('/{manufacturer}', 'destroy');
    // Route::patch('/{manufacturer}/edit', 'edit');
    Route::patch('/{manufacturer}/change/status', 'updateStatus');
    Route::patch('/{manufacturer}/suspend', 'suspend');

    Route::controller(\App\Http\Controllers\Api\V1\Admin\ManufacturerAdditionalInformationController::class)
        ->prefix('{manufacturer}/additional-information')
        ->group(function (): void {
            Route::get('/', 'index');
            Route::post('/', 'store');
        });
});

Route::get(
    '/manufacturer-additional-information/{informationRequest}',
    [\App\Http\Controllers\Api\V1\Admin\ManufacturerAdditionalInformationController::class, 'show']
);

// Category & Subscategory Managment

Route::controller(AdminCategoryAndSubCategoryController::class)->prefix('/categories')->group(function (): void {
    Route::get('/', 'index');
    Route::post('/create', 'store');
    Route::get('/{category}', 'show');
    Route::put('/{category}', 'update');
    Route::delete('/{category}', 'destroy');

    // Position update
    Route::put('/{category}/position', 'industryPosition');

    // Featured toggle
    Route::patch('/{category}/featured', 'toggleFeatured');
});

Route::controller(AdminCategoryAndSubCategoryController::class)->prefix('/subcategories')->group(function (): void {
    Route::get('/', 'indexSubcategories');
    Route::post('/create', 'storeSubcategory');
    Route::get('/{subcategory}', 'showSubcategory');
    Route::put('/{subcategory}', 'updateSubcategory');
    Route::delete('/{subcategory}', 'destroySubcategory');

    // Position update
    Route::put('/{subcategory}/position', 'subcategoryPosition');
});

// ==============Products===============
// ManageShippingMethods

Route::controller(ShippinMethodController::class)->prefix('/shipping/methods')->group(function (): void {
    Route::get('/', 'index');
    Route::post('/create', 'store');
    Route::get('/{shippingMethod}', 'show');
    Route::put('/{shippingMethod}', 'update');
    Route::delete('/{shippingMethod}', 'destroy');
});

Route::controller(AdminProductController::class)->prefix('products')->group(function () {
    Route::get('/', 'index');
    Route::patch('/{product}/approval-status', 'updateApprovalStatus');
});

Route::controller(RfqSubmissionAdminController::class)->prefix('rfqs')->group(function (): void {
    Route::get('/', 'index');
    Route::get('/{rfq}', 'show');
});

Route::controller(TicketAdminController::class)->prefix('customer-supports/tickets')->group(function (): void {
    Route::get('/', 'index');
    Route::get('/{ticket}', 'show');
    Route::patch('/{ticket}', 'update');
    Route::post('/{ticket}/messages', 'storeMessage');
});

Route::middleware(['throttle:currency-admin'])->group(function (): void {
    Route::get('/currencies', [CurrencyAdminController::class, 'indexCurrencies']);
    Route::patch('/currencies/{currency}', [CurrencyAdminController::class, 'updateCurrency']);
    Route::prefix('currency')->group(function (): void {
        Route::post('/rates', [CurrencyAdminController::class, 'storeRate']);
        Route::get('/rates/current', [CurrencyAdminController::class, 'ratesCurrent']);
        Route::get('/rates/history', [CurrencyAdminController::class, 'ratesHistory']);
        Route::post('/sync-rates', [CurrencyAdminController::class, 'syncRates']);
    });
});

Route::prefix('faqs')->group(function (): void {
    Route::controller(FaqCategoryController::class)->prefix('categories')->group(function (): void {

        Route::get('/{faqCategory}', 'show');
        Route::post('/create', 'store');
        Route::put('/{id}', 'update');
        Route::delete('/{faqCategory}', 'destroy');

        // Position update
        Route::put('/{faqCategory}/position', 'categoryPosition');
    });

    Route::controller(FaqController::class)->group(function (): void {
        Route::post('/create', 'store');
        Route::get('/{faq}', 'show');
        Route::put('/{faq}', 'update');
        Route::delete('/{faq}', 'destroy');
        Route::put('/{faq}/position', 'faqPosition');

    });
});

Route::controller(QuickFilterAdminController::class)->prefix('quick-filters')->group(function (): void {
    Route::get('/counts', 'counts');
    Route::get('/types', 'types');
    Route::get('/options', 'index');
    Route::post('/options', 'store');
    Route::get('/options/{quickFilterOption}', 'show');
    Route::put('/options/{quickFilterOption}', 'update');
    Route::delete('/options/{quickFilterOption}', 'destroy');
    Route::patch('/options/{quickFilterOption}/toggle', 'toggle');
    Route::patch('/options/{quickFilterOption}/sort', 'sort');
});

Route::controller(\App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController::class)->group(function (): void {
    Route::get('/subscriptions/stats', 'stats');
    Route::get('/subscriptions', 'index');
    Route::get('/subscriptions/{subscription}', 'show');
    Route::get('/payments', 'payments');
    Route::get('/subscription-logs', 'logs');
});

Route::prefix('plans')->group(function (): void {

    Route::controller(FeatureController::class)->prefix('features')->group(function (): void {
        Route::get('/', 'index');
        Route::put('/{feature}', 'update');
        // Route::post('/create', 'store');
    });

    Route::controller(PlanController::class)->group(function (): void {
        Route::post('/create', 'store');
        Route::get('/{plan}', 'show');
        Route::put('/{plan}', 'update');
        Route::delete('/{plan}', 'destroy');
        Route::patch('/{plan}/toggle-popular', 'togglePopular');
        Route::patch('/{plan}/toggle-status', 'toggleStatus');
    });
});

Route::prefix('certificate-types')->group(function (): void {
    Route::controller(CertificateTypeController::class)->group(function (): void {
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{certificateType}', 'show');
        Route::put('/{certificateType}', 'update');
        Route::delete('/{certificateType}', 'destroy');
        // Route::patch('/{certificateType}/toggle-status', 'toggleStatus');
    });
});

Route::prefix('certifications')->group(function (): void {
    Route::controller(CertificationController::class)->group(function (): void {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::delete('/{certificationId}', 'deleteCertificate');
    });
});

Route::resource('article/categories', ArticleCategoryController::class)->names('article.categories');

Route::prefix('promotions')->controller(PromotionController::class)->group(function (): void {
    Route::get('/', 'index');
    Route::get('/active', 'active');
    Route::post('/reset', 'reset');
    Route::get('/{promotion}', 'show');
    Route::put('/{promotion}', 'update');
    Route::patch('/{promotion}/toggle-status', 'toggleStatus');
    Route::get('/{promotion}/participants', 'participants');
    Route::post('/{promotion}/enroll', 'enroll');
    Route::patch('/{promotion}/participants/{user}', 'updateParticipantStatus');
});

Route::prefix('articles')->group(function (): void {
    Route::controller(ArticleController::class)->group(function (): void {
        Route::get('/stats', 'stats');
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{article}', 'show');
        Route::put('/{article}', 'update');
        Route::delete('/{article}', 'destroy');
        Route::patch('/{article}/toggle-status', 'toggleStatus');
    });
});

Route::controller(ContactController::class)->prefix('contacts')->group(function (): void {
    Route::get('/', 'index');
    Route::get('/{contact}', 'show');
    Route::delete('/{contact}', 'destroy');
    Route::patch('/{contact}/read-status', 'updateReadStatus');
});

Route::prefix('help-center')->group(function (): void {
    Route::prefix('categories')->controller(HelpCenterCategoryController::class)->group(function (): void {
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{helpCenterCategory}', 'show');
        Route::put('/{helpCenterCategory}', 'update');
        Route::put('/{helpCenterCategory}/position', 'updatePosition');
        Route::delete('/{helpCenterCategory}', 'destroy');
    });

    Route::prefix('articles')->controller(HelpCenterArticleController::class)->group(function (): void {
        Route::get('/', 'index');
        Route::post('/create', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::put('/{id}/position', 'updatePosition');
        Route::delete('/{id}', 'destroy');
    });
});

Route::controller(OrderController::class)->prefix('orders')->group(function (): void {
    Route::get('/stats', 'stats');
    Route::get('/', 'index');
    Route::get('/{order}', 'show');
});

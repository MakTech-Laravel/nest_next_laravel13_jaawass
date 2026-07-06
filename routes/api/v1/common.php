<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\TwoFactorApiController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Api\V1\UserPreferencesController;
use Illuminate\Support\Facades\Route;

// Route::get('/me', [AuthController::class, 'me']);
// Route::post('/logout', [AuthController::class, 'logout']);
// Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
//     ->middleware(['throttle:6,1'])
//     ->name('verification.send');

Route::controller(AuthController::class)->group(function () {
    // Route::get('/me', 'me');
    Route::post('/logout', 'logout');
});

// For All Role Users.
Route::controller(UserController::class)->group(function () {
    Route::get('/me', 'me');
});

Route::controller(UserNotificationController::class)->group(function () {
    Route::get('/me/notifications', 'index');
    Route::get('/me/notifications/unread-count', 'unreadCount');
    Route::patch('/me/notifications/{id}/read', 'markAsRead')->whereNumber('id');
    Route::post('/me/notifications/read-all', 'markAllRead');
    Route::post('/me/notifications/test-broadcast', 'storeTest')->middleware('throttle:12,1');
});

Route::middleware('manufacturer.plan.feature:internal_messaging')->group(function (): void {
    Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('conversations/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::post('conversations/{conversation}/participants', [ConversationController::class, 'addParticipants'])
        ->name('conversations.participants.store');
    Route::get('conversations/{conversation}/messages', [MessageController::class, 'index'])
        ->name('conversations.messages.index');
    Route::post('conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->name('conversations.messages.store');
});

Route::patch('/me/preferences', [UserPreferencesController::class, 'update']);
Route::patch('/me/currency-preference', [UserPreferencesController::class, 'update']);

Route::controller(AccountController::class)->prefix('account')->group(function () {
    Route::post('/deactivate', 'deactivate');
    Route::post('/activate', 'activate');
    Route::post('/delete-request', 'requestDeletion');
    Route::post('/change-password', 'changePassword');
    Route::get('/login-history', 'loginHistory');
});


Route::controller(TicketController::class)->prefix('customer-supports')->group(function (): void {
    Route::get('/options', 'options');
});

Route::controller(TicketController::class)->prefix('customer-supports/tickets')->group(function (): void {
    Route::get('/', 'index');
    Route::post('/', 'store');
    Route::get('/{ticket}', 'show')->whereNumber('ticket');
    Route::post('/{ticket}/messages', 'storeMessage')->whereNumber('ticket');
});


Route::controller(TwoFactorApiController::class)->prefix('two-factor')->group(function () {
    Route::post('/enable', 'enable');
    Route::get('/qr-code', 'qrCode');
    Route::post('/confirm', 'confirm');
    Route::get('/recovery-codes', 'recoveryCodes');
    Route::post('/recovery-codes/regenerate', 'regenerateRecoveryCodes');
    Route::delete('/disable', 'disable');
});



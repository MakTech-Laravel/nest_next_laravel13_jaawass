<?php

use App\Http\Middleware\ResolveApiLocale;
use App\Http\Middleware\ResolveApiTimezone;
use Illuminate\Support\Facades\Route;

Route::middleware([ResolveApiLocale::class])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(base_path('routes/api/v1/public.php'));

// Common Protected Routes
Route::middleware(['auth:api', ResolveApiLocale::class, ResolveApiTimezone::class])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(base_path('routes/api/v1/common.php'));

// Buyers Routes
Route::middleware(['auth:api', ResolveApiLocale::class, ResolveApiTimezone::class, 'role.buyer', 'email.verified'])
    ->prefix('v1/buyer')
    ->name('api.v1.buyer.')
    ->group(base_path('routes/api/v1/buyer.php'));

// Manufacturers Routes
Route::middleware(['auth:api', ResolveApiLocale::class, ResolveApiTimezone::class, 'role.manufacturer', 'email.verified'])
    ->prefix('v1/manufacturer')
    ->name('api.v1.manufacturer.')
    ->group(base_path('routes/api/v1/manufacturer.php'));

// Admins Routes
Route::middleware(['auth:api', ResolveApiLocale::class, ResolveApiTimezone::class, 'role.admin'])
    ->prefix('v1/admin')
    ->name('api.v1.admin.')
    ->group(base_path('routes/api/v1/admin.php'));

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:api');

<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\AztecSymbolController;
use App\Http\Controllers\Api\BeverageCategoryController;
use App\Http\Controllers\Api\BeverageController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerDebtMovementController;
use App\Http\Controllers\Api\CustomerFavoriteBeverageController;
use App\Http\Controllers\Api\CustomizationOptionController;
use App\Http\Controllers\Api\CustomizationTypeController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QrLookupController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RewardTransactionController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SizeController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VoiceSaleDraftController;
use App\Http\Controllers\Api\WorkSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('meta', MetaController::class)->name('meta');
    Route::get('catalog', CatalogController::class)->name('catalog');
    Route::post('auth/login', [AuthTokenController::class, 'store'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthTokenController::class, 'show'])->name('auth.me');
        Route::delete('auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');
        Route::apiResource('aztec-symbols', AztecSymbolController::class)->only(['index', 'show']);
        Route::get('qr/{uuid}', QrLookupController::class)->name('qr.lookup');
        Route::get('reports/overview', ReportController::class)->name('reports.overview');
        Route::apiResource('branches', BranchController::class);
        Route::apiResource('categories', BeverageCategoryController::class)->parameters([
            'categories' => 'beverageCategory',
        ]);
        Route::apiResource('sizes', SizeController::class);
        Route::apiResource('beverages', BeverageController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('customization-types', CustomizationTypeController::class);
        Route::apiResource('customization-options', CustomizationOptionController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/debt-movements', [CustomerDebtMovementController::class, 'index'])
            ->name('customers.debt-movements.index');
        Route::post('customers/{customer}/debt-movements', [CustomerDebtMovementController::class, 'store'])
            ->name('customers.debt-movements.store');
        Route::post('customers/{customer}/reward-transactions', [RewardTransactionController::class, 'store'])
            ->name('customers.reward-transactions.store');
        Route::get('customers/{customer}/favorite-beverages', CustomerFavoriteBeverageController::class)
            ->name('customers.favorite-beverages');
        Route::apiResource('users', UserController::class);
        Route::apiResource('work-sessions', WorkSessionController::class)->except(['destroy']);
        Route::apiResource('sales', SaleController::class)->only(['index', 'store', 'show']);
        Route::post('sales/voice-drafts', VoiceSaleDraftController::class)->name('sales.voice-drafts.store');
        Route::apiResource('reward-transactions', RewardTransactionController::class)->only(['index', 'show']);
    });
});

Route::get('catalog', CatalogController::class)->name('api.catalog');
Route::apiResource('customers', CustomerController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::get('qr/{uuid}', QrLookupController::class)->name('api.qr.lookup');
Route::get('reports/overview', ReportController::class)->name('api.reports.overview');
Route::get('sales', [SaleController::class, 'index'])->name('api.sales.index');
Route::post('sales', [SaleController::class, 'store'])->name('api.sales.store');
Route::get('sales/{sale}', [SaleController::class, 'show'])->name('api.sales.show');

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuthController;

// Public routes (no authentication required)
Route::middleware('json')->group(function () {
    // OAuth 2.0 endpoints
    Route::prefix('oauth')->group(function () {
        Route::post('/authorize', [OAuthController::class, 'authorize']);
        Route::post('/token', [OAuthController::class, 'token']);
        Route::post('/revoke', [OAuthController::class, 'revoke']);
        Route::get('/token-info', [OAuthController::class, 'tokenInfo']);
        Route::get('/scopes', [OAuthController::class, 'scopes']);
        Route::post('/clients', [OAuthController::class, 'createClient']);
    });

    // Authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// Protected routes (require authentication)
Route::middleware(['json', 'auth:sanctum'])->group(function () {
    // Authentication management routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    });
    
    // Additional inventory-related routes
    Route::prefix('inventory')->group(function () {
        Route::patch('/{inventory}', [InventoryController::class, 'patch']);
        Route::post('/{inventoryId}/adjust', [InventoryController::class, 'adjustQuantity']);
        Route::get('/low-stock', [InventoryController::class, 'lowStockItems']);
        Route::post('/bulk-update', [InventoryController::class, 'bulkUpdate']);
    });
    // Inventory routes lastly
    Route::apiResource('inventory', InventoryController::class);
});

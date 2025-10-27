<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuthController;

// Apply JSON middleware to all API routes
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

    // Auth endpoints
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        });
        
        // Inventory routes with additional custom methods
        Route::prefix('inventory')->group(function () {
            Route::apiResource('', InventoryController::class);
            Route::post('/{inventoryId}/adjust', [InventoryController::class, 'adjustQuantity']);
            Route::get('/low-stock', [InventoryController::class, 'lowStockItems']);
            Route::post('/bulk-update', [InventoryController::class, 'bulkUpdate']);
        });
    });
});

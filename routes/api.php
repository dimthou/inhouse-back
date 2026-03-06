<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Modules\Inventory\Controllers\StockController;
use App\Modules\Inventory\Controllers\StockMovementController;

/*
|--------------------------------------------------------------------------
| API Routes with RBAC
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
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

        // ==========================================
        // AUTHENTICATION MANAGEMENT
        // ==========================================
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/me', [UserController::class, 'me']);
        });

        // ==========================================
        // ROLE MANAGEMENT (Admin only)
        // ==========================================
        Route::middleware('role:admin')->prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('/{role}', [RoleController::class, 'show']);
            Route::put('/{role}', [RoleController::class, 'update']);
            Route::delete('/{role}', [RoleController::class, 'destroy']);

            // Role permissions management
            Route::post('/{role}/permissions/assign', [RoleController::class, 'assignPermissions']);
            Route::post('/{role}/permissions/revoke', [RoleController::class, 'revokePermissions']);
            Route::post('/{role}/permissions/sync', [RoleController::class, 'syncPermissions']);
        });

        // ==========================================
        // PERMISSION MANAGEMENT (Admin only)
        // ==========================================
        Route::middleware('role:admin')->prefix('permissions')->group(function () {
            Route::get('/', [PermissionController::class, 'index']);
            Route::get('/grouped', [PermissionController::class, 'grouped']);
            Route::get('/{permission}', [PermissionController::class, 'show']);
        });

        // ==========================================
        // USER MANAGEMENT (Admin only)
        // ==========================================
        Route::middleware('role:admin')->prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);

            // User role management
            Route::post('/{user}/roles', [UserController::class, 'assignRoles']);
            Route::post('/{user}/roles/add', [UserController::class, 'addRole']);
            Route::delete('/{user}/roles/remove', [UserController::class, 'removeRole']);
            Route::get('/{user}/permissions', [UserController::class, 'permissions']);
        });

        // ==========================================
        // INVENTORY MANAGEMENT (with RBAC)
        // ==========================================
        Route::prefix('inventory')->group(function () {

            // Low stock items (read-only)
            Route::get('/low-stock', [InventoryController::class, 'lowStockItems'])
                ->middleware('permission:inventory.view');

            // Bulk update (manager and above)
            Route::post('/bulk-update', [InventoryController::class, 'bulkUpdate'])
                ->middleware('permission:inventory.edit');

            // Adjust quantity (staff and above)
            Route::post('/{inventoryId}/adjust', [InventoryController::class, 'adjustQuantity'])
                ->middleware('permission:inventory.adjust');

            // Standard CRUD operations with permissions
            Route::get('/', [InventoryController::class, 'index'])
                ->middleware('permission:inventory.view');

            Route::post('/', [InventoryController::class, 'store'])
                ->middleware('permission:inventory.create');

            Route::get('/{inventory}', [InventoryController::class, 'show'])
                ->middleware('permission:inventory.view');

            Route::put('/{inventory}', [InventoryController::class, 'update'])
                ->middleware('permission:inventory.edit');

            Route::patch('/{inventory}', [InventoryController::class, 'patch'])
                ->middleware('permission:inventory.edit');

            Route::delete('/{inventory}', [InventoryController::class, 'destroy'])
                ->middleware('permission:inventory.delete');
        });

        // ==========================================
        // STOCK MOVEMENTS (tenant scoped)
        // ==========================================
        Route::middleware('tenant')->prefix('stocks')->group(function () {
            Route::get('/', [StockController::class, 'index'])
                ->middleware('permission:inventory.view');

            Route::post('/in', [StockMovementController::class, 'stockIn'])
                ->middleware('permission:stock.in');

            Route::post('/out', [StockMovementController::class, 'stockOut'])
                ->middleware('permission:stock.out');

            Route::post('/adjust', [StockMovementController::class, 'adjust'])
                ->middleware('permission:stock.adjust');
        });
    });
});

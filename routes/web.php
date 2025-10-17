<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LicensesController;
use App\Http\Controllers\Admin\ManageRequestItemController;
use App\Http\Controllers\Admin\ManageUserController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\StationaryItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\RequestItemController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'middleware' => ['auth', 'verified'],
], function () {

    // Asset Routes
    Route::resource('assets', AssetController::class);

    // Additional Asset Routes
    Route::put('assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.updateStatus');
    Route::post('assets/assign', [AssetController::class, 'assignToUser'])->name('assets.assign');
    Route::post('assets/bulk-assign', [AssetController::class, 'bulkAssign'])->name('assets.bulk-assign');
    Route::post('assets/bulk-update-status', [AssetController::class, 'bulkUpdateStatus'])->name('assets.bulk-update-status');
    Route::post('assets/{asset}/return', [AssetController::class, 'returnAsset'])->name('assets.return');
    Route::get('assets/{asset}/assignment-history', [AssetController::class, 'assignmentHistory'])->name('assets.assignment-history');
    Route::post('assets/export', [AssetController::class, 'export'])->name('assets.export');

    Route::resource('models', ModelController::class);

    Route::post('models/bulk-update-status', [ModelController::class, 'bulkUpdateStatus'])->name('models.bulk-update-status');
    Route::post('models/bulk-delete', [ModelController::class, 'bulkDelete'])->name('models.bulk-delete');
    Route::post('models/export', [ModelController::class, 'export'])->name('models.export');

    Route::resource('categories', CategoryController::class);

    // Additional routes
    Route::post('categories/bulk-update-status', [CategoryController::class, 'bulkUpdateStatus'])
        ->name('categories.bulk-update-status');

    Route::post('categories/bulk-delete', [CategoryController::class, 'bulkDelete'])
        ->name('categories.bulk-delete');

    Route::post('categories/export', [CategoryController::class, 'export'])
        ->name('categories.export');

    Route::resource('licenses', LicensesController::class);

    Route::post('licenses/bulk-update-status', [LicensesController::class, 'bulkUpdateStatus'])
        ->name('licenses.bulk-update-status');

    Route::post('licenses/{license}/update-quantity', [LicensesController::class, 'updateQuantity'])
        ->name('licenses.update-quantity');

    Route::get('licenses/generate-product-key', [LicensesController::class, 'generateProductKey'])
        ->name('licenses.generate-product-key');

    Route::post('licenses/export', [LicensesController::class, 'export'])
        ->name('licenses.export');

    Route::resource('users', ManageUserController::class);
    // Additional User Routes
    Route::post('/users/{user}/reset-password', [ManageUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/bulk-reset-password', [ManageUserController::class, 'bulkResetPassword'])->name('users.bulk-reset-password');
    Route::post('/users/bulk-delete', [ManageUserController::class, 'bulkDelete'])->name('users.bulk-delete');
    Route::post('/users/export', [ManageUserController::class, 'export'])->name('users.export');
    Route::get('/users/search', [ManageUserController::class, 'search'])->name('users.search');

    Route::resource('stationary-items', StationaryItemController::class);

    // Additional routes
    Route::post('stationary-items/{stationaryItem}/update-stock', [StationaryItemController::class, 'updateStock'])
        ->name('stationary-items.update-stock');

    Route::post('stationary-items/{stationaryItem}/record-movement', [StationaryItemController::class, 'recordMovement'])
        ->name('stationary-items.record-movement');

    Route::post('stationary-items/bulk-update-status', [StationaryItemController::class, 'bulkUpdateStatus'])
        ->name('stationary-items.bulk-update-status');

    Route::post('stationary-items/export', [StationaryItemController::class, 'export'])
        ->name('stationary-items.export');

    Route::get('stationary-items/{stationaryItem}/movements', [StationaryItemController::class, 'movements'])
        ->name('stationary-items.movements');
    // Movement-specific routes
    Route::delete('stationary-movements/{movement}', [StationaryItemController::class, 'destroyMovement'])
        ->name('stationary-movements.destroy');

    Route::resource('manage-request-items', ManageRequestItemController::class);

    // Additional routes for approval actions
    Route::post('manage-request-items/{manageRequestItem}/approve', [ManageRequestItemController::class, 'approve'])
        ->name('manage-request-items.approve');

    Route::post('manage-request-items/{manageRequestItem}/reject', [ManageRequestItemController::class, 'reject'])
        ->name('manage-request-items.reject');

    Route::post('manage-request-items/{manageRequestItem}/complete', [ManageRequestItemController::class, 'complete'])
        ->name('manage-request-items.complete');

    Route::post('manage-request-items/{manageRequestItem}/update-quantities', [ManageRequestItemController::class, 'updateQuantities'])
        ->name('manage-request-items.update-quantities');

    Route::resource('permissions', PermissionController::class);

    Route::post('permissions/bulk-destroy', [PermissionController::class, 'bulkDestroy'])
        ->name('permissions.bulk-destroy');

    Route::post('permissions/{permission}/remove-role/{role}', [PermissionController::class, 'removeRole'])
        ->name('admin.permissions.remove-role');

    Route::resource('audit-logs', AuditLogController::class)->only(['index', 'show']);
});

Route::group([
    'prefix' => 'user',
    'as' => 'user.',
    'middleware' => ['auth', 'verified'],
], function () {

    // Request Items Routes
    Route::resource('request-items', RequestItemController::class);

    // Cart Routes - FIXED: Use explicit routes to avoid parameter naming issues
    Route::get('cart-list', [CartController::class, 'index'])->name('cart-list.index');
    Route::post('cart-list', [CartController::class, 'store'])->name('cart-list.store');
    Route::put('cart-list/{cart}', [CartController::class, 'update'])->name('cart-list.update');
    Route::delete('cart-list/{cart}', [CartController::class, 'destroy'])->name('cart-list.destroy');
    Route::post('cart-list/clear', [CartController::class, 'clear'])->name('cart-list.clear');

    // Optional: If you need the other resource routes
    Route::get('cart-list/create', [CartController::class, 'create'])->name('cart-list.create');
    Route::get('cart-list/{cart}', [CartController::class, 'show'])->name('cart-list.show');
    Route::get('cart-list/{cart}/edit', [CartController::class, 'edit'])->name('cart-list.edit');
});

require __DIR__.'/auth.php';

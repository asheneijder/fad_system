<?php

use App\Models\User;
use Inertia\Inertia;
use App\Models\Asset;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\RequestItemController;
use App\Http\Controllers\Admin\LicensesController;
use App\Http\Controllers\Admin\UserAzureController;
use App\Http\Controllers\Admin\ManageUserController;
use App\Http\Controllers\Admin\StationaryItemController;

Route::get("/", function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users'         => (int) User::count(),
        'assets'        => (int) Asset::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

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

    Route::group(['prefix' => 'asset', 'middleware' => ['auth', 'verified']], function () {
        Route::get('view-asset', [AssetController::class, 'index'])->name('index.asset');
        Route::get('create-asset', [AssetController::class, 'create'])->name('asset.create');
        Route::post('store-asset', [AssetController::class, 'store'])->name('asset.store');
        Route::get('edit-asset/{id}', [AssetController::class, 'edit'])->name('asset.edit');
    });

    Route::group(['prefix' => 'license', 'middleware' => ['auth', 'verified']], function () {
        Route::get('view-license', [LicensesController::class, 'index'])->name('index.license');
    });

    Route::group(['prefix' => 'settings', 'middleware' => ['auth', 'verified']], function () {
        Route::get('import', [UserAzureController::class, 'index'])->name('index.import');
        Route::post('import', [UserAzureController::class, 'store'])->name('import.store');
    });

    Route::resource('users', ManageUserController::class)
        ->only([
            'index',
            'show',
            'create',
            'store',
            'edit',
            'update',
            'destroy'
        ]);

        Route::resource('stationary-items', StationaryItemController::class)
        ->only([
            'index',
            'show',
            'create',
            'store',
            'edit',
            'update',
            'destroy'
        ]);

    Route::group(['prefix' => 'request', 'middleware' => ['auth', 'verified']], function () {
        Route::get('view-request-items', [RequestItemController::class, 'index'])->name('index.request');
        Route::get('create-request-item', [RequestItemController::class, 'create'])->name('request.create');
        Route::post('store-request-item', [RequestItemController::class, 'store'])->name('request.store');
    });
});

require __DIR__ . '/auth.php';

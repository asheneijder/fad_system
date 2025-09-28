<?php

use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LicensesController;
use App\Http\Controllers\Admin\ManageUserController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\StationaryItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\CartController;
use App\Http\Controllers\User\RequestItemController;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users' => (int) User::count(),
        'assets' => (int) Asset::count(),
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

    Route::resource('assets', AssetController::class);

    Route::put('assets/{asset}/status', [AssetController::class, 'updateStatus'])->name('assets.updateStatus');

    Route::post('asset/assign', [AssetController::class, 'assignToUser'])->name('asset.assign');

    Route::resource('models', ModelController::class);

    Route::resource('categories', CategoryController::class);

    Route::resource('licenses', LicensesController::class);

    Route::resource('users', ManageUserController::class);

    Route::resource('stationary-items', StationaryItemController::class);
});

Route::group([
    'prefix' => 'user',
    'as' => 'user.',
    'middleware' => ['auth', 'verified'],
], function () {

    Route::resource('request-items', RequestItemController::class);

    Route::resource('cart-list', CartController::class);
});

require __DIR__.'/auth.php';

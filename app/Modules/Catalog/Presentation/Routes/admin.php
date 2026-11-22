<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Controllers\Admin\KitchenStationController;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\SizeTierController;
use App\Modules\Catalog\Presentation\Http\Controllers\Admin\TagController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff
// (ModuleServiceProvider). Everything here also needs the "manage-catalog" gate: admins and super admins.

Route::middleware('can:manage-catalog')->group(function (): void {
    Route::prefix('kitchen-stations')
        ->name('kitchen-stations.')
        ->where(['station' => '[0-9]+'])
        ->controller(KitchenStationController::class)
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::post('/reorder', 'reorder')->name('reorder');
            Route::get('/{station}/edit', 'edit')->name('edit');
            Route::put('/{station}', 'update')->name('update');
            Route::delete('/{station}', 'destroy')->name('destroy');
            Route::post('/{station}/restore', 'restore')->name('restore');
        });

    Route::prefix('size-tiers')
        ->name('size-tiers.')
        ->where(['sizeTier' => '[0-9]+'])
        ->controller(SizeTierController::class)
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::post('/reorder', 'reorder')->name('reorder');
            Route::get('/{sizeTier}/edit', 'edit')->name('edit');
            Route::put('/{sizeTier}', 'update')->name('update');
            Route::patch('/{sizeTier}/toggle', 'toggle')->name('toggle');
            Route::delete('/{sizeTier}', 'destroy')->name('destroy');
            Route::post('/{sizeTier}/restore', 'restore')->name('restore');
        });

    Route::prefix('tags')
        ->name('tags.')
        ->where(['tag' => '[0-9]+'])
        ->controller(TagController::class)
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::post('/reorder', 'reorder')->name('reorder');
            Route::get('/{tag}/edit', 'edit')->name('edit');
            Route::put('/{tag}', 'update')->name('update');
            Route::delete('/{tag}', 'destroy')->name('destroy');
            Route::post('/{tag}/restore', 'restore')->name('restore');
        });
});

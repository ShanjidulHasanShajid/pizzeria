<?php

declare(strict_types=1);

use App\Modules\Customization\Presentation\Http\Controllers\Admin\OptionGroupController;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\PriceMatrixController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff
// (ModuleServiceProvider). Everything here also needs the "manage-catalog" gate.

Route::middleware('can:manage-catalog')
    ->prefix('option-groups')
    ->name('option-groups.')
    ->where(['group' => '[0-9]+'])
    ->group(function (): void {
        Route::controller(OptionGroupController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::post('/reorder', 'reorder')->name('reorder');
            Route::get('/{group}/edit', 'edit')->name('edit');
            Route::put('/{group}', 'update')->name('update');
            Route::delete('/{group}', 'destroy')->name('destroy');
            Route::post('/{group}/restore', 'restore')->name('restore');
        });

        Route::controller(PriceMatrixController::class)->group(function (): void {
            Route::get('/{group}/prices', 'edit')->name('prices.edit');
            Route::put('/{group}/prices', 'update')->name('prices.update');
        });
    });

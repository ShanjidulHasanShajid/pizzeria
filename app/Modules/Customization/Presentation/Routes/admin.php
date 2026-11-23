<?php

declare(strict_types=1);

use App\Modules\Customization\Presentation\Http\Controllers\Admin\BulkAttachController;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\OptionController;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\OptionGroupController;
use App\Modules\Customization\Presentation\Http\Controllers\Admin\PriceMatrixController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff
// (ModuleServiceProvider). Everything here also needs the "manage-catalog" gate.

Route::middleware('can:manage-catalog')
    ->where(['group' => '[0-9]+', 'option' => '[0-9]+'])
    ->group(function (): void {
        Route::prefix('option-groups')->name('option-groups.')->group(function (): void {
            Route::controller(OptionGroupController::class)->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::post('/reorder', 'reorder')->name('reorder');
                Route::get('/{group}/edit', 'edit')->name('edit');
                Route::put('/{group}', 'update')->name('update');
                Route::delete('/{group}', 'destroy')->name('destroy');
                Route::post('/{group}/restore', 'restore')->name('restore');
                // "Delete forever": super admins only (business rules section 2).
                Route::delete('/{group}/force', 'forceDestroy')->middleware('can:force-delete')->name('force-destroy');
            });

            Route::controller(PriceMatrixController::class)->group(function (): void {
                Route::get('/{group}/prices', 'edit')->name('prices.edit');
                Route::put('/{group}/prices', 'update')->name('prices.update');
            });

            Route::controller(BulkAttachController::class)->group(function (): void {
                Route::get('/{group}/bulk-attach', 'create')->name('bulk-attach.create');
                Route::post('/{group}/bulk-attach', 'store')->name('bulk-attach.store');
            });

            Route::controller(OptionController::class)->group(function (): void {
                Route::get('/availability', 'availability')->name('availability');
                Route::patch('/{group}/options/{option}/availability', 'toggle')->name('options.toggle');
            });
        });

        // Single options in the Trash.
        Route::prefix('options')->name('options.')->controller(OptionController::class)->group(function (): void {
            Route::post('/{option}/restore', 'restore')->name('restore');
            Route::delete('/{option}/force', 'forceDestroy')->middleware('can:force-delete')->name('force-destroy');
        });
    });

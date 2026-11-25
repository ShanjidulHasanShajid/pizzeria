<?php

declare(strict_types=1);

use App\Modules\Ordering\Presentation\Http\Controllers\Admin\DeliveryZoneController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff (ModuleServiceProvider).

Route::middleware('can:manage-sales')
    ->prefix('delivery-zones')
    ->name('delivery-zones.')
    ->where(['zone' => '[0-9]+'])
    ->controller(DeliveryZoneController::class)
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/reorder', 'reorder')->name('reorder');
        Route::get('/{zone}/edit', 'edit')->name('edit');
        Route::put('/{zone}', 'update')->name('update');
        Route::patch('/{zone}/toggle', 'toggle')->name('toggle');
        Route::delete('/{zone}', 'destroy')->name('destroy');
        Route::post('/{zone}/restore', 'restore')->name('restore');
        Route::delete('/{zone}/force', 'forceDestroy')->middleware('can:force-delete')->name('force-destroy');
    });

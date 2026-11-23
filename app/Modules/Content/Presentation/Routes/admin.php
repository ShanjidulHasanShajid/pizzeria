<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Presentation\Http\Controllers\Admin\ContentItemController;
use App\Modules\Content\Presentation\Http\Controllers\Admin\HomeController;
use App\Modules\Content\Presentation\Http\Controllers\Admin\LocationController;
use App\Modules\Content\Presentation\Http\Controllers\Admin\NavigationController;
use App\Modules\Content\Presentation\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff (ModuleServiceProvider).

Route::middleware('can:manage-settings')
    ->prefix('settings')
    ->name('settings.')
    ->controller(SettingsController::class)
    ->group(function (): void {
        Route::get('/', 'edit')->name('edit');
        // {group} is a SettingGroup enum: any other word is a 404.
        Route::put('/{group}', 'update')->name('update');
    });

Route::middleware('can:manage-content')->group(function (): void {
    Route::prefix('home')->name('home.')->controller(HomeController::class)->group(function (): void {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    // The same routes for every content type: admin.banners.index, admin.hero-slides.edit ...
    foreach (ContentType::cases() as $type) {
        Route::prefix($type->value)
            ->name("{$type->value}.")
            ->where(['item' => '[0-9]+'])
            ->controller(ContentItemController::class)
            ->group(function () use ($type): void {
                Route::get('/', 'index')->name('index')->defaults('type', $type->value);
                Route::get('/create', 'create')->name('create')->defaults('type', $type->value);
                Route::post('/', 'store')->name('store')->defaults('type', $type->value);
                Route::post('/reorder', 'reorder')->name('reorder')->defaults('type', $type->value);
                Route::get('/{item}/edit', 'edit')->name('edit')->defaults('type', $type->value);
                Route::put('/{item}', 'update')->name('update')->defaults('type', $type->value);
                Route::patch('/{item}/toggle', 'toggle')->name('toggle')->defaults('type', $type->value);
                Route::delete('/{item}', 'destroy')->name('destroy')->defaults('type', $type->value);
                Route::post('/{item}/restore', 'restore')->name('restore')->defaults('type', $type->value);
                Route::delete('/{item}/force', 'forceDestroy')->middleware('can:force-delete')->name('force-destroy')->defaults('type', $type->value);
            });
    }

    Route::post('gallery/upload', [ContentItemController::class, 'upload'])->name('gallery.upload');

    Route::prefix('navigation')->name('navigation.')->controller(NavigationController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/reorder', 'reorder')->name('reorder');
        // {item} is a NavRoute enum: any other word is a 404.
        Route::put('/{item}', 'update')->name('update');
        Route::patch('/{item}/visibility', 'toggleVisibility')->name('visibility');
        Route::patch('/{item}/highlight', 'toggleHighlight')->name('highlight');
    });

    Route::prefix('locations')->name('locations.')->where(['location' => '[0-9]+'])->controller(LocationController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/reorder', 'reorder')->name('reorder');
        Route::get('/{location}/edit', 'edit')->name('edit');
        Route::put('/{location}', 'update')->name('update');
        Route::patch('/{location}/toggle', 'toggle')->name('toggle');
        Route::delete('/{location}', 'destroy')->name('destroy');
        Route::post('/{location}/restore', 'restore')->name('restore');
        Route::delete('/{location}/force', 'forceDestroy')->middleware('can:force-delete')->name('force-destroy');
    });
});

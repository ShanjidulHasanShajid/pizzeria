<?php

declare(strict_types=1);

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

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// This file is loaded by ModuleServiceProvider with the prefix "/admin" and names starting "admin.".
// There is NO login yet: Phase 5 adds the auth and role middleware. Never deploy before that.
Route::view('/', 'shared::admin.dashboard')->name('dashboard');

// Development only. Removed in Phase 24.
if (! app()->isProduction()) {
    Route::view('/ui-kit', 'shared::dev.admin-ui-kit')->name('ui-kit');
}

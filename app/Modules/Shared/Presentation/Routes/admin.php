<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Loaded by ModuleServiceProvider with the prefix "/admin", names starting "admin." and the
// middleware web + auth + staff. Sections that staff must NOT see add ->middleware('admin').

Route::view('/', 'shared::admin.dashboard')->name('dashboard');

// Development only. Removed in Phase 24.
if (! app()->isProduction()) {
    Route::view('/ui-kit', 'shared::dev.admin-ui-kit')->name('ui-kit');
}

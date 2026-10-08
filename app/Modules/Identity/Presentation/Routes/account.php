<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Loaded with prefix "/account", names "account." and middleware web + auth.
// Placeholder: Phase 14 (Customer account) builds the real pages and takes this over.
Route::view('/', 'identity::account.dashboard')->name('dashboard');

<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Http\Controllers\Storefront\ForgotPasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\Storefront\LoginController;
use App\Modules\Identity\Presentation\Http\Controllers\Storefront\RegisterController;
use App\Modules\Identity\Presentation\Http\Controllers\Storefront\ResetPasswordController;
use Illuminate\Support\Facades\Route;

// Loaded by ModuleServiceProvider with the "web" middleware and no prefix.
// The route NAMES (login, password.reset...) are the ones Laravel looks for.

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

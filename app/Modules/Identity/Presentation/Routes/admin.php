<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Http\Controllers\Admin\AdminUserController;
use App\Modules\Identity\Presentation\Http\Controllers\Admin\CustomerController;
use Illuminate\Support\Facades\Route;

// Loaded with prefix "/admin", names "admin." and middleware web + auth + staff.
// "can:..." asks a gate (see IdentityServiceProvider). Unauthorized users get the 403 page.

Route::middleware('can:manage-admin-users')
    ->prefix('admin-users')
    ->name('admin-users.')
    ->where(['user' => '[0-9]+'])
    ->group(function (): void {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
        Route::patch('/{user}/role', [AdminUserController::class, 'changeRole'])->name('role');
        Route::post('/{user}/block', [AdminUserController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [AdminUserController::class, 'unblock'])->name('unblock');
        Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/restore', [AdminUserController::class, 'restore'])->name('restore');
    });

Route::middleware('can:manage-customers')
    ->prefix('customers')
    ->name('customers.')
    ->where(['user' => '[0-9]+'])
    ->group(function (): void {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/{user}/block', [CustomerController::class, 'block'])->name('block');
        Route::post('/{user}/unblock', [CustomerController::class, 'unblock'])->name('unblock');
    });

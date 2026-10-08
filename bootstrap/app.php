<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Http\Middleware\EnsureAdmin;
use App\Modules\Identity\Presentation\Http\Middleware\EnsureStaff;
use App\Modules\Identity\Presentation\Http\Middleware\EnsureUserIsNotBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Gate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short names usable in route files: ->middleware('staff'), ->middleware('admin').
        $middleware->alias([
            'staff' => EnsureStaff::class,
            'admin' => EnsureAdmin::class,
        ]);

        // Runs on every web request.
        $middleware->web(append: [EnsureUserIsNotBlocked::class]);

        // Where "guest" routes (login, register) send someone who is already logged in.
        $middleware->redirectUsersTo(
            fn (): string => Gate::allows('access-admin') ? route('admin.dashboard') : route('home'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

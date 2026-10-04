<?php

declare(strict_types=1);

use App\Modules\Shared\Infrastructure\Providers\ModuleServiceProvider;

it('boots one service provider for each of the 12 modules', function () {
    $loaded = array_filter(
        array_keys(app()->getLoadedProviders()),
        fn (string $provider): bool => is_subclass_of($provider, ModuleServiceProvider::class),
    );

    expect($loaded)->toHaveCount(count(modules()));
});

it('registers the view namespace of every module', function () {
    $hints = app('view')->getFinder()->getHints();

    foreach (modules() as $module) {
        expect($hints)->toHaveKey(strtolower($module));
    }
});

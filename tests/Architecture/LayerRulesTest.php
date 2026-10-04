<?php

declare(strict_types=1);

/** @return array<int, string> */
function layersOfAllModules(string ...$layers): array
{
    $namespaces = [];

    foreach (modules() as $module) {
        foreach ($layers as $layer) {
            $namespaces[] = layer($module, $layer);
        }
    }

    return $namespaces;
}

arch('domain code does not use Laravel or Livewire')
    ->expect('App\Modules\*\Domain')
    ->not->toUse(['Illuminate', 'Livewire']);

arch('application code does not use Laravel, Eloquent, HTTP, facades or Livewire')
    ->expect('App\Modules\*\Application')
    ->not->toUse(['Illuminate', 'Livewire']);

arch('domain code does not depend on the layers around it')
    ->expect('App\Modules\*\Domain')
    ->not->toUse(layersOfAllModules('Application', 'Infrastructure', 'Presentation'));

arch('application code does not depend on infrastructure or presentation')
    ->expect('App\Modules\*\Application')
    ->not->toUse(layersOfAllModules('Infrastructure', 'Presentation'));

arch('presentation code does not use infrastructure classes')
    ->expect('App\Modules\*\Presentation')
    ->not->toUse(layersOfAllModules('Infrastructure'));

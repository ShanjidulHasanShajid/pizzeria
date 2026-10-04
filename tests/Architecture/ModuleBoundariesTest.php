<?php

declare(strict_types=1);

/*
| A module may use another module only through that module's Application layer.
| Shared is the common kernel: every module may use it, and it uses no module.
*/
foreach (modules() as $module) {
    if ($module === 'Shared') {
        continue;
    }

    $forbidden = [];

    foreach (modules() as $other) {
        if ($other === $module || $other === 'Shared') {
            continue;
        }

        $forbidden[] = layer($other, 'Domain');
        $forbidden[] = layer($other, 'Infrastructure');
        $forbidden[] = layer($other, 'Presentation');
    }

    arch("{$module} reaches other modules only through their Application layer")
        ->expect(layer($module))
        ->not->toUse($forbidden);
}

arch('Shared does not use any other module')
    ->expect(layer('Shared'))
    ->not->toUse(array_map(
        layer(...),
        array_values(array_filter(modules(), fn (string $m): bool => $m !== 'Shared')),
    ));

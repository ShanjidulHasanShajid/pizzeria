<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| Feature and Integration tests boot Laravel (TestCase) and get a clean
| pizzeria_test database (RefreshDatabase).
| Unit and Architecture tests stay plain PHP: no framework, no database.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => $this->withoutVite())
    ->in('Feature', 'Integration');

/**
 * The 12 module folder names under app/Modules.
 *
 * @return array<int, string>
 */
function modules(): array
{
    return [
        'Shared', 'Identity', 'Catalog', 'Customization', 'Content', 'Cart',
        'Ordering', 'Customers', 'Promotions', 'Reviews', 'Payments', 'Support',
    ];
}

/**
 * Namespace of one layer of one module, for example layer('Catalog', 'Domain').
 * Built with sprintf because braces next to backslashes inside double-quoted
 * strings are easy to get wrong (and Pint rewrites the backslashes).
 */
function layer(string $module, string $layer = ''): string
{
    return rtrim(sprintf('App\\Modules\\%s\\%s', $module, $layer), '\\');
}

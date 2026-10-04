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
    ->in('Feature', 'Integration');

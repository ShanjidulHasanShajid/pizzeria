<?php

declare(strict_types=1);

namespace App\Modules\Shared\Application\Ports;

use DateTimeImmutable;

/**
 * The only way business code may ask "what time is it?".
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}

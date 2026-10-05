<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Exceptions;

use RuntimeException;

/**
 * Parent of every error that means "a business rule was broken".
 * Catching this one class catches all of them.
 */
class DomainException extends RuntimeException {}

<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\SettingKey;

/**
 * One setting on the settings screen: what it is, and its current value (or its default).
 */
final readonly class SettingField
{
    public function __construct(
        public SettingKey $key,
        public string $value,
    ) {}
}

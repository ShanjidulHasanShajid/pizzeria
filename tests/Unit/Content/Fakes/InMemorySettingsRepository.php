<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Fakes;

use App\Modules\Content\Domain\Repositories\SettingsRepository;
use RuntimeException;

/**
 * A SettingsRepository in an array, for use case tests.
 */
final class InMemorySettingsRepository implements SettingsRepository
{
    private bool $failNextSave = false;

    /**
     * @param  array<string, string>  $values
     */
    public function __construct(private array $values = []) {}

    public function all(): array
    {
        return $this->values;
    }

    public function save(array $values): void
    {
        if ($this->failNextSave) {
            $this->failNextSave = false;

            throw new RuntimeException('The database is down.');
        }

        $this->values = [...$this->values, ...$values];
    }

    // Test helper (not part of the interface)

    public function failNextSave(): void
    {
        $this->failNextSave = true;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Customization\Fakes;

use App\Modules\Customization\Domain\Entities\ProductOptionGroup;
use App\Modules\Customization\Domain\Repositories\ProductOptionGroupRepository;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;

/**
 * A ProductOptionGroupRepository in an array, for use case tests. Copies in, copies out.
 */
final class InMemoryProductOptionGroupRepository implements ProductOptionGroupRepository
{
    /** @var array<int, ProductOptionGroup> */
    private array $attachments = [];

    private int $nextId = 1;

    public function find(int $id): ?ProductOptionGroup
    {
        return isset($this->attachments[$id]) ? clone $this->attachments[$id] : null;
    }

    public function forProduct(int $productId): array
    {
        $found = [];

        foreach ($this->attachments as $attachment) {
            if ($attachment->productId() === $productId) {
                $found[] = clone $attachment;
            }
        }

        usort($found, fn (ProductOptionGroup $a, ProductOptionGroup $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $found;
    }

    public function forProducts(array $productIds): array
    {
        sort($productIds);

        return array_merge(...array_map($this->forProduct(...), $productIds));
    }

    public function save(ProductOptionGroup $attachment): int
    {
        $id = $attachment->id() ?? $this->nextId;
        $this->nextId = max($this->nextId, $id + 1);
        $this->attachments[$id] = ProductOptionGroup::reconstitute(
            $id,
            $attachment->productId(),
            $attachment->optionGroupId(),
            $attachment->rules(),
            $attachment->sort(),
        );

        return $id;
    }

    public function delete(int $id): void
    {
        if (! isset($this->attachments[$id])) {
            throw EntityNotFound::withId('Attached option group', $id);
        }

        unset($this->attachments[$id]);
    }

    public function isGroupAttached(int $optionGroupId): bool
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->optionGroupId() === $optionGroupId) {
                return true;
            }
        }

        return false;
    }
}

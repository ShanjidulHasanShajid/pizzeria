<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\ValueObjects\ContentItem;
use DateTimeImmutable;

/**
 * One content item for the shop and the admin screens.
 */
final readonly class ContentItemView
{
    /**
     * @param  array<string, string>  $values  field name => value, "" for an empty one
     * @param  'visible'|'scheduled'|'expired'|'inactive'  $state
     */
    public function __construct(
        public int $id,
        public array $values,
        public ?DateTimeImmutable $startsAt,
        public ?DateTimeImmutable $endsAt,
        public bool $isActive,
        public string $state,
    ) {}

    public static function fromItem(ContentItem $item, DateTimeImmutable $now): self
    {
        $start = $item->schedule->start();

        return new self(
            (int) $item->id,
            $item->values,
            $start,
            $item->schedule->end(),
            $item->isActive,
            match (true) {
                ! $item->isActive => 'inactive',
                $item->isVisibleAt($now) => 'visible',
                $start !== null && $now < $start => 'scheduled',
                default => 'expired',
            },
        );
    }

    public function value(string $field): string
    {
        return $this->values[$field] ?? '';
    }
}

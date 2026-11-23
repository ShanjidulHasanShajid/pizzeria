<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\ValueObjects;

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\DateRange;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use DateTimeImmutable;

/**
 * One hero slide, banner, trust badge or gallery image. Plain data: a change makes a new object.
 * Its values are always complete: every field of the type, "" for an empty one.
 */
final readonly class ContentItem
{
    /**
     * @param  array<string, string>  $values  field name => value
     */
    private function __construct(
        public ?int $id,
        public ContentType $type,
        public array $values,
        public DateRange $schedule,
        public bool $isActive,
        public SortOrder $sort,
    ) {}

    /**
     * @param  array<string, string>  $values
     */
    public static function create(ContentType $type, array $values, DateRange $schedule, bool $isActive, SortOrder $sort): self
    {
        return new self(null, $type, self::checked($type, $values), self::scheduleFor($type, $schedule), $isActive, $sort);
    }

    /**
     * Rebuilds a saved item. Only the repository and tests call this.
     *
     * @param  array<string, string>  $values
     */
    public static function reconstitute(int $id, ContentType $type, array $values, DateRange $schedule, bool $isActive, SortOrder $sort): self
    {
        return new self($id, $type, self::complete($type, $values), self::scheduleFor($type, $schedule), $isActive, $sort);
    }

    /**
     * @param  array<string, string>  $values  fields that are missing keep their value
     */
    public function change(array $values, DateRange $schedule, bool $isActive): self
    {
        return new self(
            $this->id,
            $this->type,
            self::checked($this->type, [...$this->values, ...$values]),
            self::scheduleFor($this->type, $schedule),
            $isActive,
            $this->sort,
        );
    }

    public function toggled(): self
    {
        return new self($this->id, $this->type, $this->values, $this->schedule, ! $this->isActive, $this->sort);
    }

    public function movedTo(SortOrder $sort): self
    {
        return new self($this->id, $this->type, $this->values, $this->schedule, $this->isActive, $sort);
    }

    public function value(string $field): string
    {
        return $this->values[$field] ?? '';
    }

    /**
     * @return list<string> the stored paths of all its images
     */
    public function imagePaths(): array
    {
        $paths = [];

        foreach ($this->type->fields() as $field) {
            if ($field->isImage() && $this->value($field->name) !== '') {
                $paths[] = $this->value($field->name);
            }
        }

        return $paths;
    }

    /** The caller passes the moment (from the Clock port). */
    public function isVisibleAt(DateTimeImmutable $moment): bool
    {
        return $this->isActive && $this->schedule->isActiveAt($moment);
    }

    private static function scheduleFor(ContentType $type, DateRange $schedule): DateRange
    {
        return $type->isScheduled() ? $schedule : DateRange::always();
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private static function complete(ContentType $type, array $values): array
    {
        $complete = [];

        foreach ($type->fields() as $field) {
            $complete[$field->name] = trim($values[$field->name] ?? '');
        }

        return $complete;
    }

    /**
     * @param  array<string, string>  $values
     * @return array<string, string>
     */
    private static function checked(ContentType $type, array $values): array
    {
        $complete = self::complete($type, $values);
        $errors = [];

        foreach ($type->fields() as $field) {
            $problem = $field->problemWith($complete[$field->name]);

            if ($problem !== null) {
                $errors[$field->name] = [$problem];
            }
        }

        if ($errors !== []) {
            throw ValidationFailed::withErrors($errors);
        }

        return $complete;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\DTOs;

use App\Modules\Content\Domain\Enums\ContentType;
use DateTimeImmutable;

final readonly class SaveContentItemInput
{
    /**
     * @param  ?int  $id  null for a new item
     * @param  array<string, string>  $values  text fields as typed; image fields are ignored here
     * @param  array<string, ImageUpload>  $uploads  image field => new image
     * @param  list<string>  $removedImages  image fields that are emptied
     */
    public function __construct(
        public ContentType $type,
        public ?int $id,
        public array $values,
        public array $uploads,
        public array $removedImages,
        public ?DateTimeImmutable $startsAt,
        public ?DateTimeImmutable $endsAt,
        public bool $isActive,
    ) {}
}

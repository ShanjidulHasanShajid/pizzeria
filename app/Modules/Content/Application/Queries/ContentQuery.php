<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Queries;

use App\Modules\Content\Application\DTOs\ContentItemView;
use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Content\Domain\ValueObjects\HomeLayout;

interface ContentQuery
{
    /**
     * What customers see now: active items inside their dates, in sort order.
     *
     * @return list<ContentItemView>
     */
    public function visible(ContentType $type): array;

    /**
     * Everything that is not deleted, for the admin list.
     *
     * @return list<ContentItemView>
     */
    public function table(ContentType $type): array;

    /** One item that is not deleted, or null. */
    public function item(ContentType $type, int $id): ?ContentItemView;

    public function homeLayout(): HomeLayout;
}

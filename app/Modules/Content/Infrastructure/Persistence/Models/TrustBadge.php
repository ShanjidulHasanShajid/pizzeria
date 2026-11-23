<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

class TrustBadge extends ContentModel
{
    /**
     * @var list<string>
     */
    protected $fillable = ['icon', 'title', 'text', 'sort', 'is_active'];
}

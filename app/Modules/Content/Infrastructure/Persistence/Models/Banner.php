<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

class Banner extends ContentModel
{
    /**
     * @var list<string>
     */
    protected $fillable = ['title', 'link', 'image', 'starts_at', 'ends_at', 'sort', 'is_active'];
}

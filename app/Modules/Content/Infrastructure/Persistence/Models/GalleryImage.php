<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

class GalleryImage extends ContentModel
{
    /**
     * @var list<string>
     */
    protected $fillable = ['image', 'caption', 'sort', 'is_active'];
}

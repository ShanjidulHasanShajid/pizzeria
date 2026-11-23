<?php

declare(strict_types=1);

namespace App\Modules\Content\Infrastructure\Persistence\Models;

class HeroSlide extends ContentModel
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title', 'subtitle', 'button_text', 'button_link', 'image', 'mobile_image',
        'starts_at', 'ends_at', 'sort', 'is_active',
    ];
}

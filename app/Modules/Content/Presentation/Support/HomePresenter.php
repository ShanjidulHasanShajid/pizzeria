<?php

declare(strict_types=1);

namespace App\Modules\Content\Presentation\Support;

use App\Modules\Catalog\Application\DTOs\CategorySection;
use App\Modules\Content\Application\DTOs\ContentItemView;
use App\Modules\Content\Application\DTOs\HomePage;
use App\Modules\Shared\Application\Ports\FileStorage;

/**
 * Gives every section of the home page the shape its component draws.
 */
final class HomePresenter
{
    public function __construct(private readonly FileStorage $files) {}

    /**
     * @return array<string, mixed>
     */
    public function present(HomePage $page): array
    {
        return [
            'siteName' => $page->settings->siteName,
            'slides' => array_map($this->slide(...), $page->heroSlides),
            'badges' => array_map(fn (ContentItemView $badge): array => $badge->values, $page->trustBadges),
            'banners' => array_map(fn (ContentItemView $banner): array => [
                'title' => $banner->value('title'),
                'image' => $this->files->url($banner->value('image')),
                'url' => $this->link($banner->value('link')),
            ], $page->banners),
            'hotProducts' => $page->hotProducts,
            'tabs' => $this->tabs($page->tabs),
            'rows' => array_map(fn (CategorySection $row): array => ['name' => $row->name, 'slug' => $row->slug, 'products' => $row->products], $page->rows),
            'builderTitle' => $page->settings->builderTitle,
            'builderText' => $page->settings->builderText,
            'hoursText' => $page->settings->openingHoursText,
            'gallery' => $this->gallery($page->gallery),
        ];
    }

    /**
     * The button needs a text and a link.
     *
     * @return array{image: string, mobileImage: ?string, title: string, text: string, cta: string, url: ?string}
     */
    private function slide(ContentItemView $slide): array
    {
        $mobileImage = $slide->value('mobile_image');
        $hasButton = $slide->value('button_text') !== '' && $slide->value('button_link') !== '';

        return [
            'image' => $this->files->url($slide->value('image')),
            'mobileImage' => $mobileImage === '' ? null : $this->files->url($mobileImage),
            'title' => $slide->value('title'),
            'text' => $slide->value('subtitle'),
            'cta' => $slide->value('button_text'),
            'url' => $hasButton ? $this->link($slide->value('button_link')) : null,
        ];
    }

    /**
     * @param  list<CategorySection>  $sections
     * @return array<string, array<string, mixed>>
     */
    private function tabs(array $sections): array
    {
        $tabs = [];

        foreach ($sections as $section) {
            $tabs["category-{$section->id}"] = ['label' => $section->name, 'products' => $section->products];
        }

        return $tabs;
    }

    /**
     * @param  list<ContentItemView>  $photos
     * @return list<array{src: string, alt: string}>
     */
    private function gallery(array $photos): array
    {
        $images = [];

        foreach ($photos as $position => $photo) {
            $caption = $photo->value('caption');
            $images[] = [
                'src' => $this->files->url($photo->value('image')),
                'alt' => $caption === '' ? 'Photo '.($position + 1) : $caption,
            ];
        }

        return $images;
    }

    /** A path of our site becomes a full address; an https address stays as it is. */
    private function link(string $link): ?string
    {
        return $link === '' ? null : url($link);
    }
}

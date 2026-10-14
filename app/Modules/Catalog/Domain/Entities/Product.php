<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Entities;

use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductImages;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductVariants;
use App\Modules\Catalog\Domain\Exceptions\ProductNotGiftable;
use App\Modules\Catalog\Domain\ValueObjects\ImagePath;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Shared\Domain\Entities\AggregateRoot;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Something on the menu: a pizza, a side, a drink, a combo or the build-your-own pizza.
 * It is the aggregate root of its variants and images: change them only through Product,
 * because only Product sees all of them and can keep the rules true.
 * The category and the tags are separate aggregates, so Product keeps only their ids.
 */
final class Product extends AggregateRoot
{
    public const NAME_MAX_LENGTH = 150;

    public const SHORT_DESCRIPTION_MAX_LENGTH = 255;

    public const DESCRIPTION_MAX_LENGTH = 5000;

    /**
     * @param  list<Variant>  $variants
     * @param  list<ProductImage>  $images
     * @param  list<int>  $tagIds
     */
    private function __construct(
        private ?int $id,
        private readonly ProductType $type,
        private int $categoryId,
        private string $name,
        private Slug $slug,
        private ?string $shortDescription,
        private ?string $description,
        private LeadTime $leadTime,
        private ProductBadges $badges,
        private bool $isGiftable,
        private bool $isPublished,
        private bool $isAvailable,
        private SortOrder $sort,
        private array $variants,
        private array $images,
        private array $tagIds,
    ) {}

    /**
     * A new, unsaved product with its first variant, which becomes the default.
     * New products start unpublished (the admin publishes them when they are ready)
     * and available, without badges, images or tags.
     */
    public static function create(
        ProductType $type,
        int $categoryId,
        string $name,
        Slug $slug,
        LeadTime $leadTime,
        Variant $firstVariant,
        SortOrder $sort,
        ?string $shortDescription = null,
        ?string $description = null,
    ): self {
        $firstVariant->markAsDefault();
        self::guardVariants($type, [$firstVariant]);

        return new self(
            id: null,
            type: $type,
            categoryId: self::validCategoryId($categoryId),
            name: self::cleanName($name),
            slug: $slug,
            shortDescription: self::cleanText($shortDescription, self::SHORT_DESCRIPTION_MAX_LENGTH, 'short description'),
            description: self::cleanText($description, self::DESCRIPTION_MAX_LENGTH, 'description'),
            leadTime: $leadTime,
            badges: ProductBadges::none(),
            isGiftable: false,
            isPublished: false,
            isAvailable: true,
            sort: $sort,
            variants: [$firstVariant],
            images: [],
            tagIds: [],
        );
    }

    /**
     * Rebuilds a saved product from stored data. Only the mapper (Infrastructure) calls this.
     *
     * @param  list<Variant>  $variants
     * @param  list<ProductImage>  $images
     * @param  list<int>  $tagIds
     */
    public static function reconstitute(
        int $id,
        ProductType $type,
        int $categoryId,
        string $name,
        Slug $slug,
        ?string $shortDescription,
        ?string $description,
        LeadTime $leadTime,
        ProductBadges $badges,
        bool $isGiftable,
        bool $isPublished,
        bool $isAvailable,
        SortOrder $sort,
        array $variants,
        array $images,
        array $tagIds,
    ): self {
        self::guardVariants($type, $variants);
        self::guardImages($images);

        if ($isGiftable && ! $type->canBeGiftable()) {
            throw ProductNotGiftable::onlyCombos($name);
        }

        return new self(
            $id,
            $type,
            self::validCategoryId($categoryId),
            self::cleanName($name),
            $slug,
            self::cleanText($shortDescription, self::SHORT_DESCRIPTION_MAX_LENGTH, 'short description'),
            self::cleanText($description, self::DESCRIPTION_MAX_LENGTH, 'description'),
            $leadTime,
            $badges,
            $isGiftable,
            $isPublished,
            $isAvailable,
            $sort,
            $variants,
            $images,
            self::cleanTagIds($tagIds),
        );
    }

    public function rename(string $name): void
    {
        $this->name = self::cleanName($name);
    }

    public function changeSlug(Slug $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Pass null (or empty text) to remove a description.
     */
    public function describe(?string $shortDescription, ?string $description): void
    {
        $this->shortDescription = self::cleanText($shortDescription, self::SHORT_DESCRIPTION_MAX_LENGTH, 'short description');
        $this->description = self::cleanText($description, self::DESCRIPTION_MAX_LENGTH, 'description');
    }

    public function moveToCategory(int $categoryId): void
    {
        $this->categoryId = self::validCategoryId($categoryId);
    }

    public function changeLeadTime(LeadTime $leadTime): void
    {
        $this->leadTime = $leadTime;
    }

    public function changeBadges(ProductBadges $badges): void
    {
        $this->badges = $badges;
    }

    public function moveTo(SortOrder $sort): void
    {
        $this->sort = $sort;
    }

    public function publish(): void
    {
        $this->isPublished = true;
    }

    public function unpublish(): void
    {
        $this->isPublished = false;
    }

    /**
     * "Available today": the product can be ordered.
     */
    public function markAvailable(): void
    {
        $this->isAvailable = true;
    }

    /**
     * "Sold out today": the product stays on the site but cannot be ordered.
     */
    public function markSoldOut(): void
    {
        $this->isAvailable = false;
    }

    /**
     * Turns a combo into a gift box.
     */
    public function markGiftable(): void
    {
        if (! $this->type->canBeGiftable()) {
            throw ProductNotGiftable::onlyCombos($this->name);
        }

        $this->isGiftable = true;
    }

    public function unmarkGiftable(): void
    {
        $this->isGiftable = false;
    }

    /**
     * Adds a variant. It is never the default; use setDefaultVariant() for that.
     */
    public function addVariant(Variant $variant): void
    {
        $variant->unmarkAsDefault();
        $variants = [...$this->variants, $variant];

        self::guardVariants($this->type, $variants);
        $this->variants = $variants;
    }

    /**
     * Changes label, price and size of the variant called $label.
     * The change is tried on a copy first, so a broken change leaves the product untouched.
     */
    public function updateVariant(string $label, string $newLabel, Money $price, ?int $sizeTierId): void
    {
        $current = $this->variant($label);

        $changed = clone $current;
        $changed->relabel($newLabel);
        $changed->changePrice($price);
        $changed->linkToSizeTier($sizeTierId);

        $variants = array_map(
            fn (Variant $variant): Variant => $variant === $current ? $changed : $variant,
            $this->variants,
        );

        self::guardVariants($this->type, $variants);
        $this->variants = $variants;
    }

    /**
     * Removes a variant. When it was the default, the first remaining variant becomes the default.
     */
    public function removeVariant(string $label): void
    {
        $removed = $this->variant($label);

        if (count($this->variants) === 1) {
            throw InvalidProductVariants::cannotRemoveLastVariant();
        }

        $this->variants = array_values(array_filter(
            $this->variants,
            fn (Variant $variant): bool => $variant !== $removed,
        ));

        if ($removed->isDefault()) {
            $this->variants()[0]->markAsDefault();
        }
    }

    public function setDefaultVariant(string $label): void
    {
        $newDefault = $this->variant($label);

        foreach ($this->variants as $variant) {
            $variant->unmarkAsDefault();
        }

        $newDefault->markAsDefault();
    }

    public function markVariantAvailable(string $label): void
    {
        $this->variant($label)->markAvailable();
    }

    public function markVariantSoldOut(string $label): void
    {
        $this->variant($label)->markSoldOut();
    }

    /**
     * Adds an image at the end. The first image of a product becomes the main image.
     */
    public function addImage(ImagePath $path): void
    {
        $image = ProductImage::create($path, $this->nextImageSort());

        if ($this->images === []) {
            $image->markAsMain();
        }

        $images = [...$this->images, $image];

        self::guardImages($images);
        $this->images = $images;
    }

    /**
     * Removes an image and returns its paths, so the caller can delete the files.
     * When it was the main image, the first remaining image becomes the main one.
     */
    public function removeImage(string $originalPath): ImagePath
    {
        $removed = $this->image($originalPath);

        $this->images = array_values(array_filter(
            $this->images,
            fn (ProductImage $image): bool => $image !== $removed,
        ));

        if ($removed->isMain() && $this->images !== []) {
            $this->images()[0]->markAsMain();
        }

        return $removed->path();
    }

    public function setMainImage(string $originalPath): void
    {
        $newMain = $this->image($originalPath);

        foreach ($this->images as $image) {
            $image->unmarkAsMain();
        }

        $newMain->markAsMain();
    }

    /**
     * Puts the images in the given order (10, 20, 30 ...). Every image must be listed exactly once.
     *
     * @param  list<string>  $originalPaths
     */
    public function reorderImages(array $originalPaths): void
    {
        $current = array_map(fn (ProductImage $image): string => $image->path()->original(), $this->images);
        $wanted = $originalPaths;

        sort($current);
        sort($wanted);

        if ($current !== $wanted) {
            throw InvalidProductImages::reorderNeedsEveryImage();
        }

        $sort = SortOrder::first();

        foreach ($originalPaths as $path) {
            $this->image($path)->moveTo($sort);
            $sort = $sort->next();
        }
    }

    /**
     * Replaces the product's tags. Duplicates are ignored.
     *
     * @param  list<int>  $tagIds
     */
    public function assignTags(array $tagIds): void
    {
        $this->tagIds = self::cleanTagIds($tagIds);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function type(): ProductType
    {
        return $this->type;
    }

    public function categoryId(): int
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function shortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function leadTime(): LeadTime
    {
        return $this->leadTime;
    }

    public function badges(): ProductBadges
    {
        return $this->badges;
    }

    public function isGiftable(): bool
    {
        return $this->isGiftable;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function sort(): SortOrder
    {
        return $this->sort;
    }

    /**
     * @return list<Variant> in display order
     */
    public function variants(): array
    {
        $variants = $this->variants;
        usort($variants, fn (Variant $a, Variant $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $variants;
    }

    /**
     * The variant with this label (compared without case and outer spaces).
     */
    public function variant(string $label): Variant
    {
        foreach ($this->variants as $variant) {
            if ($variant->hasLabel($label)) {
                return $variant;
            }
        }

        throw InvalidProductVariants::notFound($label);
    }

    public function defaultVariant(): Variant
    {
        foreach ($this->variants as $variant) {
            if ($variant->isDefault()) {
                return $variant;
            }
        }

        // Cannot happen once the rules (6.14) are in place: there is always exactly one default.
        throw InvalidProductVariants::notExactlyOneDefault();
    }

    /**
     * @return list<ProductImage> in display order
     */
    public function images(): array
    {
        $images = $this->images;
        usort($images, fn (ProductImage $a, ProductImage $b): int => $a->sort()->value() <=> $b->sort()->value());

        return $images;
    }

    /**
     * The main image, or null when the product has no images.
     */
    public function mainImage(): ?ProductImage
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return null;
    }

    public function image(string $originalPath): ProductImage
    {
        foreach ($this->images as $image) {
            if ($image->path()->original() === $originalPath) {
                return $image;
            }
        }

        throw InvalidProductImages::notFound($originalPath);
    }

    /**
     * @return list<int> sorted, without duplicates
     */
    public function tagIds(): array
    {
        return $this->tagIds;
    }

    private function nextImageSort(): SortOrder
    {
        $images = $this->images();
        $last = end($images);

        return $last === false ? SortOrder::first() : $last->sort()->next();
    }

    /**
     * The variant rules. Every change checks a candidate list BEFORE it replaces the
     * current one, so a change that breaks a rule leaves the product exactly as it was.
     *
     * @param  list<Variant>  $variants
     */
    private static function guardVariants(ProductType $type, array $variants): void
    {
        if ($variants === []) {
            throw InvalidProductVariants::noVariants();
        }

        if ($type->allowsOnlyOneVariant() && count($variants) !== 1) {
            throw InvalidProductVariants::comboNeedsExactlyOneVariant();
        }

        $defaults = 0;
        $labels = [];
        $sizeTierIds = [];

        foreach ($variants as $variant) {
            if ($variant->isDefault()) {
                $defaults++;
            }

            $label = mb_strtolower($variant->label());

            if (isset($labels[$label])) {
                throw InvalidProductVariants::duplicateLabel($variant->label());
            }

            $labels[$label] = true;

            if (! $type->needsSizeTierOnEveryVariant()) {
                continue;
            }

            $sizeTierId = $variant->sizeTierId();

            if ($sizeTierId === null) {
                throw InvalidProductVariants::builderVariantNeedsSizeTier($variant->label());
            }

            if (isset($sizeTierIds[$sizeTierId])) {
                throw InvalidProductVariants::sizeTierUsedTwice();
            }

            $sizeTierIds[$sizeTierId] = true;
        }

        if ($defaults !== 1) {
            throw InvalidProductVariants::notExactlyOneDefault();
        }
    }

    /**
     * The image rules: no image twice, and exactly one main image when there are images.
     *
     * @param  list<ProductImage>  $images
     */
    private static function guardImages(array $images): void
    {
        $paths = [];
        $mains = 0;

        foreach ($images as $image) {
            $path = $image->path()->original();

            if (isset($paths[$path])) {
                throw InvalidProductImages::duplicate($path);
            }

            $paths[$path] = true;

            if ($image->isMain()) {
                $mains++;
            }
        }

        if ($images !== [] && $mains !== 1) {
            throw InvalidProductImages::notExactlyOneMain();
        }
    }

    private static function validCategoryId(int $categoryId): int
    {
        if ($categoryId < 1) {
            throw InvalidValue::because('Choose a category for the product.');
        }

        return $categoryId;
    }

    private static function cleanName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw InvalidValue::because('A product name must be 1 to 150 characters.');
        }

        return $name;
    }

    /**
     * Empty text is stored as null.
     */
    private static function cleanText(?string $text, int $maxLength, string $field): ?string
    {
        $text = $text === null ? '' : trim($text);

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > $maxLength) {
            throw InvalidValue::because("A product {$field} may be up to {$maxLength} characters.");
        }

        return $text;
    }

    /**
     * @param  array<int>  $tagIds
     * @return list<int>
     */
    private static function cleanTagIds(array $tagIds): array
    {
        foreach ($tagIds as $tagId) {
            if ($tagId < 1) {
                throw InvalidValue::because('Choose valid tags.');
            }
        }

        $tagIds = array_values(array_unique($tagIds));
        sort($tagIds);

        return $tagIds;
    }
}

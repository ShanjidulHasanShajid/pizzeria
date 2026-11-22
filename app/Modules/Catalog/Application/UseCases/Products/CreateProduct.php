<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\ProductFormInput;
use App\Modules\Catalog\Application\Services\ProductInputMapper;
use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductVariants;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\Services\SlugGenerator;
use App\Modules\Shared\Domain\ValueObjects\Slug;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;

/**
 * Creates a standard product with all its variants and tags in one save. Returns its id.
 * Combos (Phase 15) and the build-your-own pizza (Phase 16) are created by their own screens.
 * Images are added on the edit page, once the product exists.
 */
final class CreateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductInputMapper $mapper,
        private readonly SlugGenerator $slugs,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(ProductFormInput $form): int
    {
        $this->mapper->assertReferencesExist($form);
        $variants = $this->mapper->variants($form);

        if ($variants === []) {
            throw ValidationFailed::forField('variants', 'Add at least one variant.');
        }

        try {
            $product = Product::create(
                type: ProductType::Standard,
                categoryId: $form->categoryId,
                name: $form->name,
                slug: $this->slugFor($form),
                leadTime: LeadTime::of($form->leadTimeClass, $form->leadTimeMinutes),
                // create() makes its first variant the default, so it gets a copy;
                // replaceVariants() then sets the real list with the default the admin chose.
                firstVariant: clone $variants[0],
                sort: SortOrder::first(),
                shortDescription: $form->shortDescription,
                description: $form->description,
            );
            $product->replaceVariants($variants);
        } catch (InvalidProductVariants $rule) {
            throw ValidationFailed::forField('variants', $rule->getMessage());
        }

        $product->changeBadges(ProductBadges::of(isNew: $form->isNew, isHot: $form->isHot));
        $product->assignTags($form->tagIds);

        if ($form->isPublished) {
            $product->publish();
        }

        if (! $form->isAvailable) {
            $product->markSoldOut();
        }

        // The repository saves the product row, the variant rows and the tag links. The
        // TransactionManager makes the whole use case one unit (7.21), also when more steps
        // are added later.
        return $this->transactions->run(fn (): int => $this->products->save($product));
    }

    private function slugFor(ProductFormInput $form): Slug
    {
        $typed = trim($form->slug ?? '');

        if ($typed === '') {
            return $this->slugs->generate($form->name, fn (Slug $slug): bool => $this->products->slugExists($slug));
        }

        $slug = Slug::fromString($typed);

        if ($this->products->slugExists($slug)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a product in the Trash). Choose another one.');
        }

        return $slug;
    }
}

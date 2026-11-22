<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\UseCases\Products;

use App\Modules\Catalog\Application\DTOs\UpdateProductInput;
use App\Modules\Catalog\Application\Services\ProductInputMapper;
use App\Modules\Catalog\Domain\Entities\Product;
use App\Modules\Catalog\Domain\Exceptions\InvalidProductVariants;
use App\Modules\Catalog\Domain\Repositories\ProductRepository;
use App\Modules\Catalog\Domain\ValueObjects\LeadTime;
use App\Modules\Catalog\Domain\ValueObjects\ProductBadges;
use App\Modules\Shared\Application\Ports\TransactionManager;
use App\Modules\Shared\Domain\Exceptions\EntityNotFound;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;
use App\Modules\Shared\Domain\ValueObjects\Slug;

/**
 * Saves the product edit form: details, variants (add, change, remove, reorder,
 * default, sold out: all at once), tags, timing, badges and visibility (7.17, 7.18, 7.20).
 * One transaction (7.21): the product, its variants and its tag links change together or not at all.
 */
final class UpdateProduct
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly ProductInputMapper $mapper,
        private readonly TransactionManager $transactions,
    ) {}

    public function handle(UpdateProductInput $input): void
    {
        $form = $input->form;

        $this->transactions->run(function () use ($input, $form): void {
            $product = $this->products->find($input->id)
                ?? throw EntityNotFound::withId('Product', $input->id);

            $this->mapper->assertReferencesExist($form);
            $this->changeSlugIfTyped($product, $input);

            try {
                $product->replaceVariants($this->mapper->variants($form));
            } catch (InvalidProductVariants $rule) {
                throw ValidationFailed::forField('variants', $rule->getMessage());
            }

            $product->rename($form->name);
            $product->describe($form->shortDescription, $form->description);
            $product->moveToCategory($form->categoryId);
            $product->changeLeadTime(LeadTime::of($form->leadTimeClass, $form->leadTimeMinutes));
            $product->changeBadges(ProductBadges::of(isNew: $form->isNew, isHot: $form->isHot));
            $product->assignTags($form->tagIds);

            if ($form->isPublished) {
                $product->publish();
            } else {
                $product->unpublish();
            }

            if ($form->isAvailable) {
                $product->markAvailable();
            } else {
                $product->markSoldOut();
            }

            $this->products->save($product);
        });
    }

    private function changeSlugIfTyped(Product $product, UpdateProductInput $input): void
    {
        $typed = trim($input->form->slug ?? '');

        if ($typed === '') {
            return;
        }

        $slug = Slug::fromString($typed);

        if ($this->products->slugExists($slug, ignoreId: $input->id)) {
            throw ValidationFailed::forField('slug', 'This slug is already used (maybe by a product in the Trash). Choose another one.');
        }

        $product->changeSlug($slug);
    }
}

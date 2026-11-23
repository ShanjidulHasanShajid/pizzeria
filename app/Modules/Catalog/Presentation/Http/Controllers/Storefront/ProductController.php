<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Controllers\Storefront;

use App\Modules\Catalog\Application\DTOs\ProductImageView;
use App\Modules\Catalog\Application\Queries\MenuQuery;
use App\Modules\Catalog\Application\Queries\StorefrontCatalogQuery;
use App\Modules\Shared\Application\Ports\FileStorage;
use Illuminate\Contracts\View\View;

final class ProductController
{
    private const RELATED_PRODUCTS = 4;

    public function show(string $product, StorefrontCatalogQuery $catalog, MenuQuery $menu, FileStorage $files): View
    {
        $detail = $catalog->productDetail($product);

        if ($detail === null) {
            abort(404);
        }

        return view('catalog::storefront.product', [
            'product' => $detail,
            'category' => $menu->category($detail->categorySlug),
            'images' => array_map(fn (ProductImageView $image): array => [
                'full' => $files->url($image->originalPath),
                'thumbnail' => $files->url($image->thumbnailPath),
                'isMain' => $image->isMain,
            ], $detail->images),
            'related' => $catalog->relatedProducts($detail->id, self::RELATED_PRODUCTS),
        ]);
    }
}

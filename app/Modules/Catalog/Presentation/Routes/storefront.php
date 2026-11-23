<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Controllers\Storefront\MenuController;
use App\Modules\Catalog\Presentation\Http\Controllers\Storefront\ProductController;
use App\Modules\Catalog\Presentation\Http\Controllers\Storefront\SearchController;
use App\Modules\Catalog\Presentation\Http\Controllers\Storefront\SitemapController;
use Illuminate\Support\Facades\Route;

Route::controller(MenuController::class)
    ->where(['category' => '[a-z0-9-]+', 'subcategory' => '[a-z0-9-]+', 'tag' => '[a-z0-9-]+'])
    ->group(function (): void {
        Route::get('/menu', 'index')->name('menu.index');
        Route::get('/menu/{category}', 'category')->name('categories.show');
        Route::get('/menu/{category}/{subcategory}', 'subcategory')->name('categories.sub');
        Route::get('/tags/{tag}', 'tag')->name('tags.show');
    });

Route::get('/product/{product}', [ProductController::class, 'show'])->where('product', '[a-z0-9-]+')->name('products.show');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/suggestions', [SearchController::class, 'suggestions'])->middleware('throttle:search-suggestions')->name('search.suggestions');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

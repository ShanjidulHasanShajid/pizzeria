<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
| Phase 4 placeholder pages. Route::view() renders a view without a controller.
| The third argument is data that becomes variables in the view ($title, $phase...).
| Each real page replaces its line in the phase named here, and its route moves
| into its own module (Catalog, Cart, Ordering...).
*/
$placeholder = 'shared::storefront.placeholder';

Route::view('/', 'shared::storefront.home')->name('home');

Route::view('/menu', $placeholder, ['title' => 'Menu', 'phase' => 10, 'showProducts' => true])->name('menu.index');
Route::view('/menu/{category}', $placeholder, ['title' => 'Category', 'phase' => 10, 'showProducts' => true])->name('categories.show');
Route::view('/products/{product}', $placeholder, ['title' => 'Product', 'phase' => 10])->name('products.show');
Route::view('/build-your-pizza', $placeholder, ['title' => 'Build your pizza', 'phase' => 16])->name('builder.show');
Route::view('/gift-boxes', $placeholder, ['title' => 'Gift boxes', 'phase' => 15])->name('gift-boxes.index');
Route::view('/meal-deals', $placeholder, ['title' => 'Meal deals', 'phase' => 15])->name('meal-deals.index');
Route::view('/our-story', $placeholder, ['title' => 'Our story', 'phase' => 17])->name('about');
Route::view('/locations', $placeholder, ['title' => 'Locations', 'phase' => 17])->name('locations.index');
Route::view('/contact', $placeholder, ['title' => 'Contact us', 'phase' => 17])->name('contact');
Route::view('/faq', $placeholder, ['title' => 'FAQ', 'phase' => 17])->name('faq');
Route::view('/catering', $placeholder, ['title' => 'Catering', 'phase' => 17])->name('catering');
Route::view('/search', $placeholder, ['title' => 'Search', 'phase' => 10])->name('search');
Route::view('/cart', $placeholder, ['title' => 'Cart', 'phase' => 11])->name('cart.show');
Route::view('/checkout', $placeholder, ['title' => 'Checkout', 'phase' => 12])->name('checkout.show');
Route::view('/order-success', $placeholder, ['title' => 'Order placed', 'phase' => 12])->name('orders.success');
Route::view('/track-order', $placeholder, ['title' => 'Track your order', 'phase' => 12])->name('orders.track');
Route::view('/terms', $placeholder, ['title' => 'Terms and conditions', 'phase' => 17])->name('legal.terms');
Route::view('/privacy', $placeholder, ['title' => 'Privacy policy', 'phase' => 17])->name('legal.privacy');
Route::view('/refund', $placeholder, ['title' => 'Refund policy', 'phase' => 17])->name('legal.refund');

// Development-only pages. Registered only outside production. Removed in Phase 24.
if (! app()->isProduction()) {
    Route::view('/ui-kit', 'shared::dev.ui-kit')->name('ui-kit');

    // Preview the branded error pages: /_errors/404, /_errors/500 ...
    Route::get('/_errors/{code}', fn (int $code) => abort($code))
        ->whereIn('code', [403, 404, 419, 429, 500, 503])
        ->name('dev.errors');
}

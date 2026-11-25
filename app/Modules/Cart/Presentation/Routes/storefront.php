<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// The page only holds the Livewire component cart.cart-page, so it needs no controller.
Route::view('/cart', 'cart::storefront.cart')->name('cart.show');

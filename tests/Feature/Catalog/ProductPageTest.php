<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\Enums\LeadTimeClass;
use App\Modules\Catalog\Domain\Enums\ProductType;
use App\Modules\Catalog\Domain\Enums\TagType;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductImage as ProductImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\ProductVariant as ProductVariantModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Tag as TagModel;

beforeEach(function (): void {
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza Corner', 'slug' => 'pizza']);
    $this->product = fn (array $attributes = [], ?CategoryModel $category = null): ProductModel => ProductModel::factory()
        ->for($category ?? $this->pizza)->create(['name' => 'Margherita', 'slug' => 'margherita', ...$attributes]);
});

it('shows a product with its details', function (): void {
    $product = ($this->product)([
        'short_description' => 'Tomato and basil.', 'description' => "First line.\n<script>alert(1)</script>",
        'is_new' => true, 'lead_time_class' => LeadTimeClass::MadeToOrder,
    ]);
    $product->tags()->attach(TagModel::factory()->create(['name' => 'Vegetarian', 'slug' => 'vegetarian', 'type' => TagType::Dietary]));

    $this->get('/product/margherita')->assertOk()
        ->assertSee('<title>Margherita', false)
        ->assertSee('<meta name="description" content="Tomato and basil.">', false)
        ->assertSeeInOrder(['Home', 'Menu', 'Pizza Corner', 'Margherita', 'New', 'Tomato and basil.', 'Vegetarian', 'First line.'])
        ->assertSee('href="'.url('/menu/pizza').'"', false)
        ->assertSee('href="'.url('/tags/vegetarian').'"', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSeeLivewire('customization.product-configurator')
        ->assertSee('Add to cart');
});

it('shows the path of a sub-category', function (): void {
    $drinks = CategoryModel::factory()->create(['name' => 'Cold Drinks', 'slug' => 'drinks']);
    $coffee = CategoryModel::factory()->childOf($drinks)->create(['name' => 'Iced Coffee', 'slug' => 'coffee']);
    ($this->product)(['name' => 'Latte', 'slug' => 'latte'], $coffee);

    $this->get('/product/latte')->assertOk()
        ->assertSeeInOrder(['Cold Drinks', 'Iced Coffee', 'Latte'])
        ->assertSee('href="'.url('/menu/drinks/coffee').'"', false);
});

it('shows the gallery with the main photo first, or a placeholder', function (): void {
    $product = ($this->product)();
    $this->get('/product/margherita')->assertSee('images/placeholder.svg')->assertDontSee('Show photo 1');

    ProductImageModel::factory()->for($product)->create(['path' => 'products/side.webp', 'thumbnail_path' => 'products/thumbs/side.webp', 'sort' => 10]);
    ProductImageModel::factory()->for($product)->main()->create(['path' => 'products/front.webp', 'thumbnail_path' => 'products/thumbs/front.webp', 'sort' => 20]);

    $this->get('/product/margherita')
        ->assertSeeInOrder(['products/front.webp', 'alt="Margherita"', 'thumbs/side.webp', 'thumbs/front.webp'], false)
        ->assertSee('Show photo 2');
});

it('answers 404 for products customers cannot see', function (string $slug): void {
    ($this->product)(['slug' => 'unpublished', 'is_published' => false]);
    ($this->product)(['slug' => 'deleted'])->delete();
    ($this->product)(['slug' => 'closed'], CategoryModel::factory()->inactive()->create());

    $this->get("/product/{$slug}")->assertNotFound();
})->with(['unknown', 'unpublished', 'deleted', 'closed', 'Not-A-Slug!']);

it('shows a sold-out product without a way to choose', function (): void {
    ($this->product)(['is_available' => false]);

    $this->get('/product/margherita')->assertOk()
        ->assertSee('sold out right now')
        ->assertDontSeeLivewire('customization.product-configurator')
        ->assertDontSee('Add to cart');
});

it('treats a product whose sizes are all sold out as sold out', function (): void {
    ($this->product)()->variants()->update(['is_available' => false]);

    $this->get('/product/margherita')->assertOk()->assertSee('sold out right now');
});

it('starts with the default size, or with the first one that can be ordered', function (bool $isDefaultAvailable, string $label): void {
    $product = ProductModel::factory()->for($this->pizza)
        ->has(ProductVariantModel::factory()->state(['label' => 'Small', 'sort' => 10]), 'variants')
        ->has(ProductVariantModel::factory()->asDefault()->state(['label' => 'Medium', 'sort' => 20, 'is_available' => $isDefaultAvailable]), 'variants')
        ->create(['slug' => 'sized']);

    $this->get('/product/sized')->assertOk()->assertViewHas('product', fn ($detail): bool => $detail->startVariant()->label === $label && $detail->id === $product->id);
})->with([[true, 'Medium'], [false, 'Small']]);

it('sends builder products to the pizza builder and keeps combos closed for now', function (): void {
    ($this->product)(['slug' => 'own', 'type' => ProductType::Builder]);
    ($this->product)(['slug' => 'deal', 'type' => ProductType::Combo]);

    $this->get('/product/own')->assertOk()->assertSee('href="'.route('builder.show').'"', false)->assertSee('Start building')->assertDontSeeLivewire('customization.product-configurator');
    $this->get('/product/deal')->assertOk()->assertSee('Customize combo')->assertDontSeeLivewire('customization.product-configurator');
});

it('shows other products of the category', function (): void {
    ($this->product)();
    ($this->product)(['name' => 'Pepperoni', 'slug' => 'pepperoni']);
    ($this->product)(['name' => 'Far Away', 'slug' => 'far'], CategoryModel::factory()->create());

    $this->get('/product/margherita')->assertSeeInOrder(['You may also like', 'Pepperoni'])->assertDontSee('Far Away');
    $this->get('/product/far')->assertDontSee('You may also like');
});

it('is linked from the cards', function (): void {
    ($this->product)();

    $this->get('/menu/pizza')->assertSee('href="'.url('/product/margherita').'"', false);
});

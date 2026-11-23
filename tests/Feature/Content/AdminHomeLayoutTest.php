<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Content\Application\Queries\ContentQuery;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedHomeLayoutRepository;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(CachedHomeLayoutRepository::CACHE_KEY);
    $this->actingAs(User::factory()->admin()->create());
    $this->pizza = CategoryModel::factory()->create(['name' => 'Pizza']);
    $this->pasta = CategoryModel::factory()->create(['name' => 'Pasta']);
    $this->margherita = ProductModel::factory()->for($this->pizza)->create(['name' => 'Margherita']);
    $this->layout = fn () => app(ContentQuery::class)->homeLayout();
});

it('saves tabs and rows and leaves out empty choices', function (): void {
    $this->get('/admin/home')->assertOk()->assertSee('Showcase tabs')->assertSee('Pizza');

    $this->put('/admin/home', [
        'tabs' => [$this->pasta->id, '', $this->pizza->id],
        'rows' => [['category_id' => ''], ['category_id' => $this->pizza->id, 'product_ids' => [$this->margherita->id, '', '']]],
    ])->assertRedirect('/admin/home')->assertSessionHas('success');

    expect(($this->layout)()->tabCategoryIds)->toBe([$this->pasta->id, $this->pizza->id])
        ->and(($this->layout)()->rows)->toHaveCount(1)
        ->and(($this->layout)()->rows[0]->productIds)->toBe([$this->margherita->id]);
    $this->get('/admin/home')->assertSee('Margherita');
});

it('shows errors for choices that do not work', function (): void {
    $this->put('/admin/home', ['tabs' => [$this->pizza->id, $this->pizza->id]])->assertSessionHasErrors('tabs');
    $this->put('/admin/home', ['tabs' => [424242]])->assertSessionHasErrors('tabs');
    $this->put('/admin/home', ['rows' => [['category_id' => $this->pasta->id, 'product_ids' => [$this->margherita->id]]]])->assertSessionHasErrors('rows.0');

    $this->assertDatabaseCount('home_sections', 0);
});

it('does not let staff change the home page', function (): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->get('/admin/home')->assertForbidden();
    $this->put('/admin/home', ['tabs' => [$this->pizza->id]])->assertForbidden();

    $this->assertDatabaseCount('home_sections', 0);
});

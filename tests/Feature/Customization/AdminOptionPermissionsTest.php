<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;

/*
| Every option route that only admins may use: route name => [method, address].
| The gate answers before anything is looked up, so the ids do not have to exist.
*/
$adminOnly = [
    'admin.option-groups.index' => ['GET', '/admin/option-groups'],
    'admin.option-groups.create' => ['GET', '/admin/option-groups/create'],
    'admin.option-groups.store' => ['POST', '/admin/option-groups'],
    'admin.option-groups.reorder' => ['POST', '/admin/option-groups/reorder'],
    'admin.option-groups.edit' => ['GET', '/admin/option-groups/1/edit'],
    'admin.option-groups.update' => ['PUT', '/admin/option-groups/1'],
    'admin.option-groups.destroy' => ['DELETE', '/admin/option-groups/1'],
    'admin.option-groups.restore' => ['POST', '/admin/option-groups/1/restore'],
    'admin.option-groups.force-destroy' => ['DELETE', '/admin/option-groups/1/force'],
    'admin.option-groups.prices.edit' => ['GET', '/admin/option-groups/1/prices'],
    'admin.option-groups.prices.update' => ['PUT', '/admin/option-groups/1/prices'],
    'admin.option-groups.bulk-attach.create' => ['GET', '/admin/option-groups/1/bulk-attach'],
    'admin.option-groups.bulk-attach.store' => ['POST', '/admin/option-groups/1/bulk-attach'],
    'admin.options.restore' => ['POST', '/admin/options/1/restore'],
    'admin.options.force-destroy' => ['DELETE', '/admin/options/1/force'],
];

it('refuses staff', function (string $method, string $uri): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->call($method, $uri)->assertForbidden();
})->with($adminOnly);

it('lets only super admins delete forever', function (string $uri): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->delete($uri)->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->delete($uri)->assertNotFound(); // allowed, but there is nothing with this id
})->with(['/admin/option-groups/1/force', '/admin/options/1/force']);

it('knows every option route, so a new one cannot be forgotten in this test', function () use ($adminOnly): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RouteObject $route): string => (string) $route->getName())
        ->filter(fn (string $name): bool => str_starts_with($name, 'admin.option-groups.') || str_starts_with($name, 'admin.options.'))
        ->values()
        ->all();

    expect($names)->toEqualCanonicalizing(array_keys($adminOnly));
});

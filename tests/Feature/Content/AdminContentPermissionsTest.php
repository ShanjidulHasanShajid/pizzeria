<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Enums\ContentType;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;

/*
| Every content route, all of them for admins only: route name => [method, address].
| The gate answers before anything is looked up, so the ids do not have to exist.
*/
$adminOnly = [
    'admin.settings.edit' => ['GET', '/admin/settings'],
    'admin.settings.update' => ['PUT', '/admin/settings/general'],
    'admin.home.edit' => ['GET', '/admin/home'],
    'admin.home.update' => ['PUT', '/admin/home'],
    'admin.gallery.upload' => ['POST', '/admin/gallery/upload'],
    'admin.navigation.index' => ['GET', '/admin/navigation'],
    'admin.navigation.reorder' => ['POST', '/admin/navigation/reorder'],
    'admin.navigation.update' => ['PUT', '/admin/navigation/menu'],
    'admin.navigation.visibility' => ['PATCH', '/admin/navigation/menu/visibility'],
    'admin.navigation.highlight' => ['PATCH', '/admin/navigation/menu/highlight'],
];

foreach ([...array_map(fn (ContentType $type): string => $type->value, ContentType::cases()), 'locations'] as $list) {
    $adminOnly += [
        "admin.{$list}.index" => ['GET', "/admin/{$list}"],
        "admin.{$list}.create" => ['GET', "/admin/{$list}/create"],
        "admin.{$list}.store" => ['POST', "/admin/{$list}"],
        "admin.{$list}.reorder" => ['POST', "/admin/{$list}/reorder"],
        "admin.{$list}.edit" => ['GET', "/admin/{$list}/1/edit"],
        "admin.{$list}.update" => ['PUT', "/admin/{$list}/1"],
        "admin.{$list}.toggle" => ['PATCH', "/admin/{$list}/1/toggle"],
        "admin.{$list}.destroy" => ['DELETE', "/admin/{$list}/1"],
        "admin.{$list}.restore" => ['POST', "/admin/{$list}/1/restore"],
        "admin.{$list}.force-destroy" => ['DELETE', "/admin/{$list}/1/force"],
    ];
}

it('refuses staff', function (string $method, string $uri): void {
    $this->actingAs(User::factory()->staff()->create());

    $this->call($method, $uri)->assertForbidden();
})->with($adminOnly);

it('sends guests to the login page', function () use ($adminOnly): void {
    foreach ($adminOnly as [$method, $uri]) {
        $this->call($method, $uri)->assertRedirect('/login');
    }
});

it('lets only super admins delete forever', function (string $list): void {
    $this->actingAs(User::factory()->admin()->create());
    $this->delete("/admin/{$list}/1/force")->assertForbidden();

    $this->actingAs(User::factory()->superAdmin()->create());
    $this->delete("/admin/{$list}/1/force")->assertNotFound(); // allowed, but there is nothing with this id
})->with(['hero-slides', 'banners', 'trust-badges', 'gallery', 'locations']);

it('knows every content route, so a new one cannot be forgotten in this test', function () use ($adminOnly): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RouteObject $route): bool => str_contains($route->getActionName(), 'App\\Modules\\Content\\') && str_starts_with((string) $route->getName(), 'admin.'))
        ->map(fn (RouteObject $route): string => (string) $route->getName())
        ->values()
        ->all();

    expect($names)->toEqualCanonicalizing(array_keys($adminOnly));
});

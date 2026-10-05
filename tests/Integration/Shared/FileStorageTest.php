<?php

declare(strict_types=1);

use App\Modules\Shared\Application\Ports\FileStorage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('stores a file under a unique name in the given directory', function () {
    $path = app(FileStorage::class)->store('products', 'hello', 'txt');

    expect($path)->toStartWith('products/')->toEndWith('.txt');
    Storage::disk('public')->assertExists($path);
    expect(Storage::disk('public')->get($path))->toBe('hello');
});

it('never reuses a name', function () {
    $storage = app(FileStorage::class);

    expect($storage->store('products', 'a', 'txt'))->not->toBe($storage->store('products', 'a', 'txt'));
});

it('accepts an extension written with a leading dot', function () {
    expect(app(FileStorage::class)->store('products', 'a', '.webp'))->toEndWith('.webp');
});

it('deletes a stored file', function () {
    $storage = app(FileStorage::class);
    $path = $storage->store('products', 'hello', 'txt');

    $storage->delete($path);

    Storage::disk('public')->assertMissing($path);
});

it('gives a url that contains the path', function () {
    $storage = app(FileStorage::class);
    $path = $storage->store('products', 'hello', 'txt');

    expect($storage->url($path))->toContain($path);
});

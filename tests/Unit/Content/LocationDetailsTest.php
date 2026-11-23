<?php

declare(strict_types=1);

use App\Modules\Content\Domain\ValueObjects\Coordinates;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\MapEmbedUrl;
use App\Modules\Shared\Domain\Exceptions\InvalidValue;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

const MAP_URL = 'https://www.google.com/maps/embed?pb=!1m18!1m12!2sDhaka&hl=en';

it('accepts a genuine Google Maps embed address, also inside the copied code', function (string $input): void {
    expect(MapEmbedUrl::fromInput($input)->value())->toBe(MAP_URL);
})->with([
    'the address' => [MAP_URL],
    'with spaces around it' => ['  '.MAP_URL."\n"],
    'the code' => ['<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!2sDhaka&amp;hl=en" width="600" style="border:0;" allowfullscreen=""></iframe>'],
]);

it('refuses every other address', function (string $input): void {
    MapEmbedUrl::fromInput($input);
})->throws(InvalidValue::class)->with([
    'http' => ['http://www.google.com/maps/embed?pb=x'],
    'another site' => ['https://evil.example/maps/embed?pb=x'],
    'a site that starts like Google' => ['https://www.google.com.evil.example/maps/embed?pb=x'],
    'a user name that looks like Google' => ['https://www.google.com@evil.example/maps/embed?pb=x'],
    'a normal maps link' => ['https://www.google.com/maps/place/Dhaka'],
    'without a map' => ['https://www.google.com/maps/embed?pb='],
    'an open redirect' => ['https://www.google.com/url?q=https://evil.example'],
    'javascript' => ['javascript:alert(1)'],
    'a quote that ends the attribute' => ['https://www.google.com/maps/embed?pb=x" onload="alert(1)'],
    'foreign code' => ['<iframe src="https://evil.example/"></iframe>'],
    'too long' => ['https://www.google.com/maps/embed?pb='.str_repeat('x', 1000)],
    'empty' => [''],
]);

it('keeps coordinates with at most seven decimals', function (): void {
    $point = Coordinates::fromStrings(' 23.746466000000003 ', '-90.3760');

    expect($point->latitude)->toBe('23.746466')
        ->and($point->longitude)->toBe('-90.376')
        ->and(Coordinates::fromStrings('90', '0')->latitude)->toBe('90');
});

it('refuses impossible coordinates', function (string $latitude, string $longitude): void {
    Coordinates::fromStrings($latitude, $longitude);
})->throws(InvalidValue::class)->with([
    ['90.0001', '0'], ['0', '-180.5'], ['north', '0'], ['23.7', ''], ['1e1', '0'],
]);

it('reports every wrong field at once', function (): void {
    try {
        LocationDetails::fromInput(' ', str_repeat('x', 256), 'Dhanmondi', 'call us', 'https://evil.example', '23.7', '', true);
    } catch (ValidationFailed $failure) {
        expect(array_keys($failure->errors()))->toBe(['name', 'address', 'phone', 'map_embed_url', 'latitude']);

        return;
    }

    throw new RuntimeException('Wrong fields must be refused.');
});

it('needs only a name, an address and a phone', function (): void {
    $details = LocationDetails::fromInput(' Dhanmondi ', 'Road 27', '', '16247', '', '', '', false);

    expect($details->name)->toBe('Dhanmondi')
        ->and($details->phone)->toBe('16247')
        ->and($details->mapEmbedUrl)->toBe('')
        ->and($details->coordinates)->toBeNull()
        ->and(fn () => LocationDetails::fromInput('Dhanmondi', 'Road 27', '', '', '', '', '', false))->toThrow(ValidationFailed::class);
});

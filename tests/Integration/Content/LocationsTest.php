<?php

declare(strict_types=1);

use App\Modules\Content\Application\DTOs\LocationView;
use App\Modules\Content\Application\Queries\BranchScheduleQuery;
use App\Modules\Content\Application\Queries\LocationQuery;
use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use App\Modules\Shared\Infrastructure\Adapters\FrozenClock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Cache::forget(CachedLocationRepository::CACHE_KEY);
    $this->locations = app(LocationRepository::class);
    $this->details = fn (string $name): LocationDetails => LocationDetails::fromInput(
        $name, 'Road 27', 'Dhanmondi', '01712 345678', 'https://www.google.com/maps/embed?pb=!1m18', '23.7465', '90.376', true,
    );
    $this->hours = OpeningHours::fromWeek(['monday' => ['18:00', '02:00']]);
    $this->save = fn (string $name, int $sort = 10, bool $isActive = true): int => $this->locations->save(
        Location::create(($this->details)($name), $this->hours, $isActive, SortOrder::fromInt($sort)),
    );
});

it('saves and loads every field, from the database and from the cache', function (): void {
    $id = ($this->save)('Dhanmondi', 30);
    $this->locations->all();

    foreach ([$this->locations->find($id), $this->locations->all()[0]] as $location) {
        expect($location->id)->toBe($id)
            ->and($location->details)->toEqual(($this->details)('Dhanmondi'))
            ->and($location->hours)->toEqual($this->hours)
            ->and($location->isActive)->toBeTrue()
            ->and($location->sort->value())->toBe(30);
    }
});

it('saves a location without the optional fields', function (): void {
    $details = LocationDetails::fromInput('Gulshan', 'Road 11', '', '16247', '', '', '', false);
    $id = $this->locations->save(Location::create($details, OpeningHours::alwaysClosed(), false, SortOrder::first()));

    expect($this->locations->find($id)->details)->toEqual($details)
        ->and($this->locations->all()[0]->details)->toEqual($details);
    $this->assertDatabaseHas('locations', ['id' => $id, 'area' => null, 'map_embed_url' => null, 'latitude' => null]);
});

it('moves a location to the Trash and back, and gives the next position', function (): void {
    $id = ($this->save)('Dhanmondi', 20);
    expect($this->locations->nextSortOrder()->value())->toBe(30);

    $this->locations->delete($id);
    $this->assertSoftDeleted('locations', ['id' => $id]);
    expect($this->locations->find($id))->toBeNull()
        ->and($this->locations->nextSortOrder()->value())->toBe(10);

    $this->locations->restore($id);
    expect($this->locations->all())->toHaveCount(1);
    expect(fn () => $this->locations->restore($id))->toThrow(ModelNotFoundException::class);
});

it('clears the cached list after every change and reads it only once', function (): void {
    $names = fn (): array => array_map(fn (Location $location): string => $location->details->name, $this->locations->all());

    expect($names())->toBe([]);
    $id = ($this->save)('Dhanmondi');
    expect($names())->toBe(['Dhanmondi']);
    $this->locations->save($this->locations->find($id)->change(($this->details)('Gulshan'), $this->hours, true));
    expect($names())->toBe(['Gulshan']);
    $this->locations->delete($id);
    expect($names())->toBe([]);
    $this->locations->restore($id);
    expect($names())->toBe(['Gulshan']);

    DB::enableQueryLog();
    $names();
    expect(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'locations')))->toBe([]);
});

it('works out "open now" with the clock, also after midnight', function (): void {
    $clock = FrozenClock::at('2026-11-23 17:00');
    $this->app->instance(Clock::class, $clock);
    $id = ($this->save)('Dhanmondi', 10);
    ($this->save)('Gulshan', 20, isActive: false);
    $query = app(LocationQuery::class);
    $open = fn (): array => array_map(fn (LocationView $location): bool => $location->isOpenNow, $query->table());

    expect($open())->toBe([false, false])
        ->and($query->active())->toHaveCount(1);

    $clock->advance('+8 hours'); // Tuesday 01:00
    expect($open())->toBe([true, false])
        ->and($query->location($id)->isOpenNow)->toBeTrue()
        ->and(app(BranchScheduleQuery::class)->isOpenAt($id, $clock->now()))->toBeTrue()
        ->and($query->location(424242))->toBeNull();
});

it('deletes a location of the Trash forever, and no other', function (): void {
    $id = ($this->save)('Dhanmondi');
    expect(fn () => $this->locations->forceDelete($id))->toThrow(ModelNotFoundException::class);

    $this->locations->delete($id);
    $this->locations->forceDelete($id);

    $this->assertDatabaseCount('locations', 0);
});

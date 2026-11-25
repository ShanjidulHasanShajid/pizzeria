<?php

declare(strict_types=1);

use App\Modules\Content\Domain\Repositories\LocationRepository;
use App\Modules\Content\Domain\ValueObjects\Location;
use App\Modules\Content\Domain\ValueObjects\LocationDetails;
use App\Modules\Content\Domain\ValueObjects\OpeningHours;
use App\Modules\Content\Infrastructure\Database\Seeders\ContentProductionSeeder;
use App\Modules\Content\Infrastructure\Persistence\Repositories\CachedLocationRepository;
use App\Modules\Ordering\Application\DTOs\DeliveryZoneRow;
use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Ordering\Application\Queries\DeliveryZoneUsageQuery;
use App\Modules\Ordering\Domain\Entities\DeliveryZone;
use App\Modules\Ordering\Domain\Repositories\DeliveryZoneRepository;
use App\Modules\Ordering\Domain\ValueObjects\DeliveryZoneDetails;
use App\Modules\Ordering\Infrastructure\Database\Seeders\OrderingDevelopmentSeeder;
use App\Modules\Shared\Domain\ValueObjects\SortOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    Cache::forget(CachedLocationRepository::CACHE_KEY);
    $this->locations = app(LocationRepository::class);
    $this->zones = app(DeliveryZoneRepository::class);
    $this->query = app(AdminDeliveryZoneQuery::class);
    $this->branch = fn (string $name, bool $isActive = true): int => $this->locations->save(Location::create(
        LocationDetails::fromInput($name, 'Road 27', '', '16247', '', '', '', true), OpeningHours::alwaysClosed(), $isActive, SortOrder::first(),
    ));
    $this->zone = fn (int $locationId, string $name = 'Banani', int $sort = 10): int => $this->zones->save(
        DeliveryZone::create(DeliveryZoneDetails::fromInput($name, $locationId, '80.50', '300'), true, SortOrder::fromInt($sort)),
    );
});

it('saves, loads and changes every field', function (): void {
    $first = ($this->branch)('First');
    $second = ($this->branch)('Second');
    $id = ($this->zone)($first, 'Banani', 30);

    $zone = $this->zones->find($id);

    expect($zone->id())->toBe($id)
        ->and($zone->details())->toEqual(DeliveryZoneDetails::fromInput('Banani', $first, '80.50', '300'))
        ->and($zone->isActive())->toBeTrue()
        ->and($zone->sort()->value())->toBe(30);

    $zone->change(DeliveryZoneDetails::fromInput('Gulshan', $second, '0', ''), false);
    $zone->moveTo(SortOrder::fromInt(50));
    expect($this->zones->save($zone))->toBe($id);

    $this->assertDatabaseCount('delivery_zones', 1);
    $this->assertDatabaseHas('delivery_zones', ['id' => $id, 'name' => 'Gulshan', 'location_id' => $second, 'fee' => 0, 'min_order' => 0, 'is_active' => false, 'sort' => 50]);
});

it('moves a zone to the Trash and back, and gives the next position', function (): void {
    $id = ($this->zone)(($this->branch)('First'), 'Banani', 20);
    expect($this->zones->nextSortOrder()->value())->toBe(30);

    $this->zones->delete($id);
    $this->assertSoftDeleted('delivery_zones', ['id' => $id]);
    expect($this->zones->find($id))->toBeNull()
        ->and($this->zones->nextSortOrder()->value())->toBe(10)
        ->and($this->query->trashed()[0]->name)->toBe('Banani');

    $this->zones->restore($id);
    expect($this->zones->find($id))->not->toBeNull()
        ->and(fn () => $this->zones->restore($id))->toThrow(ModelNotFoundException::class);
});

it('deletes only a zone of the Trash forever', function (): void {
    $id = ($this->zone)(($this->branch)('First'));
    expect(fn () => $this->zones->forceDelete($id))->toThrow(ModelNotFoundException::class);

    $this->zones->delete($id);
    $this->zones->forceDelete($id);

    $this->assertDatabaseCount('delivery_zones', 0);
});

it('lists zones in order with their branch', function (): void {
    $open = ($this->branch)('Open');
    $off = ($this->branch)('Off', isActive: false);
    $gone = ($this->branch)('Gone');
    ($this->zone)($gone, 'Third', 30);
    ($this->zone)($open, 'First', 10);
    ($this->zone)($off, 'Second', 20);
    $this->locations->delete($gone);

    $rows = $this->query->table();

    expect(array_map(fn (DeliveryZoneRow $row): string => $row->name, $rows))->toBe(['First', 'Second', 'Third'])
        ->and($rows[0]->branch?->name)->toBe('Open')
        ->and($rows[1]->branch?->isActive)->toBeFalse()
        ->and($rows[2]->branch)->toBeNull()
        ->and($rows[2]->locationId)->toBe($gone)
        ->and($rows[0]->fee->poisha())->toBe(8050)
        ->and($this->query->zone($rows[2]->id)?->branch)->toBeNull()
        ->and($this->query->zone(424242))->toBeNull();
});

it('lists many zones with one query', function (): void {
    $branch = ($this->branch)('Open');

    foreach (range(1, 5) as $number) {
        ($this->zone)($branch, "Zone {$number}", $number * 10);
    }

    app(Branches::class)->all(); // fills the cache
    DB::enableQueryLog();
    $this->query->table();

    expect(array_filter(DB::getQueryLog(), fn (array $query): bool => str_contains($query['query'], 'delivery_zones')))->toHaveCount(1);
});

it('gives the branches that are not deleted', function (): void {
    $open = ($this->branch)('Open');
    $off = ($this->branch)('Off', isActive: false);
    $this->locations->delete(($this->branch)('Gone'));
    $branches = app(Branches::class);

    expect($branches->all())->toHaveCount(2)
        ->and($branches->find($open)?->isActive)->toBeTrue()
        ->and($branches->find($off)?->isActive)->toBeFalse()
        ->and($branches->find(424242))->toBeNull();
});

it('knows whether a branch has zones, also in the Trash', function (): void {
    $used = ($this->branch)('Used');
    $free = ($this->branch)('Free');
    $this->zones->delete(($this->zone)($used));
    $usage = app(DeliveryZoneUsageQuery::class);

    expect($usage->anyForLocation($used))->toBeTrue()
        ->and($usage->anyForLocation($free))->toBeFalse();

    // The last safety net: the database refuses too.
    $this->locations->delete($used);
    expect(fn () => $this->locations->forceDelete($used))->toThrow(QueryException::class);
});

it('seeds sample zones for development, once, and none without an active branch', function (): void {
    $this->seed(ContentProductionSeeder::class); // one branch, switched off
    $this->seed(OrderingDevelopmentSeeder::class);
    expect($this->query->table())->toBe([]);

    ($this->branch)('Gulshan');
    ($this->branch)('Dhanmondi');
    $this->seed(OrderingDevelopmentSeeder::class);
    $this->seed(OrderingDevelopmentSeeder::class);

    expect($this->query->table())->toHaveCount(4)
        ->and($this->query->table()[0]->fee->poisha())->toBe(8000);
});

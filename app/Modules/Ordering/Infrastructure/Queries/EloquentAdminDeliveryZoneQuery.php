<?php

declare(strict_types=1);

namespace App\Modules\Ordering\Infrastructure\Queries;

use App\Modules\Ordering\Application\DTOs\Branch;
use App\Modules\Ordering\Application\DTOs\DeliveryZoneRow;
use App\Modules\Ordering\Application\DTOs\TrashedDeliveryZone;
use App\Modules\Ordering\Application\Ports\Branches;
use App\Modules\Ordering\Application\Queries\AdminDeliveryZoneQuery;
use App\Modules\Ordering\Infrastructure\Persistence\Models\DeliveryZone;
use App\Modules\Shared\Domain\ValueObjects\Money;
use DateTimeImmutable;

final class EloquentAdminDeliveryZoneQuery implements AdminDeliveryZoneQuery
{
    public function __construct(private readonly Branches $branches) {}

    public function table(): array
    {
        $branches = [];

        foreach ($this->branches->all() as $branch) {
            $branches[$branch->id] = $branch;
        }

        $rows = [];

        foreach (DeliveryZone::query()->orderBy('sort')->orderBy('id')->get() as $zone) {
            $rows[] = $this->toRow($zone, $branches[$zone->location_id] ?? null);
        }

        return $rows;
    }

    public function zone(int $id): ?DeliveryZoneRow
    {
        $zone = DeliveryZone::query()->find($id);

        return $zone === null ? null : $this->toRow($zone, $this->branches->find($zone->location_id));
    }

    public function trashed(): array
    {
        $items = [];

        foreach (DeliveryZone::onlyTrashed()->get() as $zone) {
            $items[] = new TrashedDeliveryZone($zone->id, $zone->name, $zone->deleted_at?->toDateTimeImmutable() ?? new DateTimeImmutable);
        }

        return $items;
    }

    private function toRow(DeliveryZone $zone, ?Branch $branch): DeliveryZoneRow
    {
        return new DeliveryZoneRow(
            $zone->id,
            $zone->name,
            $zone->location_id,
            $branch,
            Money::fromPoisha($zone->fee),
            Money::fromPoisha($zone->min_order),
            $zone->is_active,
        );
    }
}

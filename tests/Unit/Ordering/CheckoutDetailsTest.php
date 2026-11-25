<?php

declare(strict_types=1);

use App\Modules\Ordering\Domain\Enums\OrderType;
use App\Modules\Ordering\Domain\ValueObjects\CustomerDetails;
use App\Modules\Ordering\Domain\ValueObjects\FulfilmentDetails;
use App\Modules\Ordering\Domain\ValueObjects\GiftDetails;
use App\Modules\Shared\Domain\Exceptions\ValidationFailed;

/**
 * The fields that were refused.
 *
 * @return list<string>
 */
function refusedFields(Closure $action): array
{
    try {
        $action();
    } catch (ValidationFailed $failure) {
        return array_keys($failure->errors());
    }

    return [];
}

describe('customer', function (): void {
    it('cleans and normalizes the contact', function (): void {
        $customer = CustomerDetails::fromInput('  Rahim   Uddin ', '+880 1712-345678', ' Rahim@Example.com ');

        expect($customer->name)->toBe('Rahim Uddin')
            ->and($customer->phone->value())->toBe('01712345678')
            ->and($customer->email?->value())->toBe('rahim@example.com');
    });

    it('needs no email', function (): void {
        expect(CustomerDetails::fromInput('Rahim', '01712345678', '  ')->email)->toBeNull();
    });

    it('reports all problems together', function (): void {
        expect(refusedFields(fn () => CustomerDetails::fromInput(' ', '12345', 'rahim@')))->toBe(['name', 'phone', 'email']);
    });

    it('refuses a name that is too long or broken', function (string $name): void {
        expect(refusedFields(fn () => CustomerDetails::fromInput($name, '01712345678', '')))->toBe(['name']);
    })->with(['too long' => str_repeat('x', 256), 'not valid text' => "Rah\xffim"]);
});

describe('fulfilment', function (): void {
    it('holds a delivery as soon as possible', function (): void {
        $delivery = FulfilmentDetails::delivery(1, 'Gulshan branch', 2, 'Banani', " House 5\r\nRoad 11 ", null, '');

        expect($delivery->type)->toBe(OrderType::Delivery)
            ->and($delivery->locationName)->toBe('Gulshan branch')
            ->and($delivery->zoneName)->toBe('Banani')
            ->and($delivery->address)->toBe("House 5\nRoad 11")
            ->and($delivery->isAsSoonAsPossible())->toBeTrue()
            ->and($delivery->notes)->toBeNull();
    });

    it('holds a scheduled pickup without zone and address', function (): void {
        $pickup = FulfilmentDetails::pickup(1, 'Gulshan branch', new DateTimeImmutable('2026-11-26 18:00'), 'Two bags, please');

        expect($pickup->type)->toBe(OrderType::Pickup)
            ->and($pickup->zoneId)->toBeNull()
            ->and($pickup->address)->toBeNull()
            ->and($pickup->isAsSoonAsPossible())->toBeFalse()
            ->and($pickup->notes)->toBe('Two bags, please');
    });

    it('needs an address for a delivery and limits the texts', function (string $address, string $notes, array $fields): void {
        expect(refusedFields(fn () => FulfilmentDetails::delivery(1, 'Gulshan branch', 2, 'Banani', $address, null, $notes)))->toBe($fields);
    })->with([
        'no address' => ["\r\n ", '', ['delivery_address']],
        'address too long' => [str_repeat('x', 501), '', ['delivery_address']],
        'notes too long' => ['House 5', str_repeat('x', 501), ['notes']],
        'both' => ['', str_repeat('x', 501), ['delivery_address', 'notes']],
        'just fits' => [str_repeat('x', 500), str_repeat('x', 500), []],
    ]);

    it('limits the notes of a pickup', function (): void {
        expect(refusedFields(fn () => FulfilmentDetails::pickup(1, 'Gulshan branch', null, str_repeat('x', 501))))->toBe(['notes']);
    });
});

describe('gift', function (): void {
    it('holds the recipient and an optional message', function (): void {
        $gift = GiftDetails::fromInput(' Karim ', '01812345678', '');

        expect($gift->recipientName)->toBe('Karim')
            ->and($gift->recipientPhone->value())->toBe('01812345678')
            ->and($gift->message)->toBeNull()
            ->and(GiftDetails::fromInput('Karim', '01812345678', "Happy\r\nbirthday!")->message)->toBe("Happy\nbirthday!");
    });

    it('reports all problems together', function (): void {
        expect(refusedFields(fn () => GiftDetails::fromInput('', '', str_repeat('x', 301))))->toBe(['recipient_name', 'recipient_phone', 'gift_message']);
    });
});

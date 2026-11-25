<?php

declare(strict_types=1);

use App\Modules\Cart\Domain\Entities\Cart;
use App\Modules\Cart\Domain\Entities\CartLine;
use App\Modules\Cart\Domain\Repositories\CartRepository;
use App\Modules\Cart\Domain\ValueObjects\CartOwner;
use App\Modules\Cart\Domain\ValueObjects\ChosenOptions;
use App\Modules\Cart\Domain\ValueObjects\KitchenNote;
use App\Modules\Cart\Infrastructure\Persistence\Models\Cart as CartModel;
use App\Modules\Cart\Infrastructure\Persistence\Models\CartLine as CartLineModel;
use App\Modules\Catalog\Infrastructure\Persistence\Models\Product as ProductModel;
use App\Modules\Identity\Infrastructure\Persistence\Models\User;
use App\Modules\Shared\Domain\ValueObjects\Money;
use App\Modules\Shared\Domain\ValueObjects\Quantity;

beforeEach(function (): void {
    $this->repository = app(CartRepository::class);
    $this->guest = CartOwner::guest(str_repeat('a', 40));

    $product = ProductModel::factory()->create();
    $this->product = $product;
    $this->line = fn (string $note = '', int $quantity = 1, array $choices = []): CartLine => CartLine::standard(
        $product->id,
        $product->variants()->firstOrFail()->id,
        ChosenOptions::of($choices),
        KitchenNote::of($note),
        Quantity::of($quantity),
        Money::fromTaka('450.50'),
    );
    $this->cartOf = function (CartOwner $owner): Cart {
        $this->repository->createIfMissing($owner);

        return $this->repository->find($owner);
    };
});

it('finds nothing for an owner without a cart', function (): void {
    expect($this->repository->find($this->guest))->toBeNull();
});

it('creates one cart per owner, also when it is asked twice', function (): void {
    $user = User::factory()->create();

    $this->repository->createIfMissing($this->guest);
    $this->repository->createIfMissing($this->guest);
    $this->repository->createIfMissing(CartOwner::customer($user->id));

    expect(CartModel::query()->count())->toBe(2)
        ->and($this->repository->find($this->guest)->owner()->equals($this->guest))->toBeTrue()
        ->and($this->repository->find(CartOwner::customer($user->id))->owner()->userId())->toBe($user->id);
});

it('saves a line and reads the same line back', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)('less spicy', 3, [7 => [9, 4], 2 => [1]]));
    $this->repository->save($cart);

    $line = $this->repository->find($this->guest)->lines()[0];

    expect($line->id())->toBeInt()
        ->and($line->productId())->toBe($this->product->id)
        ->and($line->options()->toArray())->toBe([2 => [1], 7 => [4, 9]])
        ->and($line->note()->text())->toBe('less spicy')
        ->and($line->quantity()->value())->toBe(3)
        ->and($line->lastUnitPrice()->poisha())->toBe(45_050);
});

it('reads a line without options and without a note back', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)());
    $this->repository->save($cart);

    $line = $this->repository->find($this->guest)->lines()[0];

    expect($line->options()->toArray())->toBe([])
        ->and($line->note()->isEmpty())->toBeTrue();
});

it('stores ids only, no prices, in the selections', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)(choices: [7 => [4]]));
    $this->repository->save($cart);

    expect(CartLineModel::query()->firstOrFail()->getRawOriginal('selections'))->toBe('{"7":[4]}');
});

it('updates, keeps, inserts and deletes lines in one save', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)('one'));
    $cart->addLine(($this->line)('two'));
    $cart->addLine(($this->line)('three'));
    $this->repository->save($cart);

    $cart = $this->repository->find($this->guest);
    [$one, $two] = $cart->lines();
    $cart->removeLine($one->id());
    $cart->changeQuantity($two->id(), Quantity::of(5));
    $cart->addLine(($this->line)('four'));
    $this->repository->save($cart);

    $lines = $this->repository->find($this->guest)->lines();

    expect(array_map(fn (CartLine $line): ?string => $line->note()->text(), $lines))->toBe(['two', 'three', 'four'])
        ->and($lines[0]->id())->toBe($two->id())
        ->and($lines[0]->quantity()->value())->toBe(5);
});

it('never touches the lines of another cart', function (): void {
    $other = CartOwner::guest(str_repeat('b', 40));
    $theirs = ($this->cartOf)($other);
    $theirs->addLine(($this->line)());
    $this->repository->save($theirs);

    $mine = ($this->cartOf)($this->guest);
    $mine->clear();
    $this->repository->save($mine);

    expect($this->repository->find($other)->lines())->toHaveCount(1);
});

it('counts a changed line as a change of the cart', function (): void {
    $cart = ($this->cartOf)($this->guest);
    CartModel::query()->update(['updated_at' => '2020-01-01 12:00:00']);

    $cart->addLine(($this->line)());
    $this->repository->save($cart);

    expect(CartModel::query()->firstOrFail()->updated_at->isAfter('2020-01-01 12:00:00'))->toBeTrue();
});

it('deletes a cart with its lines', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)());
    $this->repository->save($cart);

    $this->repository->delete($cart);

    expect(CartModel::query()->count())->toBe(0)
        ->and(CartLineModel::query()->count())->toBe(0);
});

it('keeps the line of a product in the Trash and loses it when the product is deleted forever', function (): void {
    $cart = ($this->cartOf)($this->guest);
    $cart->addLine(($this->line)());
    $this->repository->save($cart);

    $this->product->delete();
    expect($this->repository->find($this->guest)->lines())->toHaveCount(1);

    $this->product->forceDelete();
    expect($this->repository->find($this->guest)->lines())->toBe([]);
});

it('can lock carts that exist and owners without a cart', function (): void {
    $this->repository->createIfMissing($this->guest);

    $this->repository->lock($this->guest, CartOwner::customer(424242));

    expect($this->repository->find($this->guest))->not->toBeNull();
});

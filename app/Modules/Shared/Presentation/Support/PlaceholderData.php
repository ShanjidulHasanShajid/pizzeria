<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

use Illuminate\Support\Str;

/**
 * TEMPORARY fake products for the placeholder pages and the UI kit.
 *
 * Phase 10 replaces every method here with real query services and this class is deleted.
 * Money is always an integer in poisha (59000 = ৳590).
 */
final class PlaceholderData
{
    public function image(): string
    {
        return asset('images/placeholder.svg');
    }

    /**
     * @param  array<int, string>  $badges
     * @return array{id: int, name: string, slug: string, description: string, price: int, state: string, badges: array<int, array{variant: string, label: string}>, image: string}
     */
    private function product(int $id, string $name, int $price, string $state, array $badges = [], string $description = ''): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $description !== '' ? $description : 'Freshly made to order with quality ingredients.',
            'price' => $price,
            'state' => $state,   // simple | options | combo | soldout
            'badges' => array_map(fn (string $badge): array => ['variant' => $badge, 'label' => ucfirst($badge)], $badges), // new | hot | veg | spicy
            'image' => $this->image(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(int $count = 8, int $offset = 0): array
    {
        $all = [
            $this->product(1, 'Margherita', 59000, 'options', ['veg'], 'Tomato, mozzarella and fresh basil.'),
            $this->product(2, 'Pepperoni Feast', 79000, 'options', ['hot'], 'Double pepperoni and melted cheese.'),
            $this->product(3, 'BBQ Chicken', 85000, 'options', ['new'], 'Smoky BBQ sauce, chicken and onions.'),
            $this->product(4, 'Garlic Bread', 24000, 'simple', ['veg']),
            $this->product(5, 'Chicken Wings', 32000, 'simple', ['spicy', 'hot']),
            $this->product(6, 'Creamy Alfredo Pasta', 49000, 'options'),
            $this->product(7, 'Chicken Pie', 28000, 'simple'),
            $this->product(8, 'Caesar Salad', 35000, 'simple', ['new']),
            $this->product(9, 'Beef Burger', 39000, 'simple', ['hot']),
            $this->product(10, 'Chocolate Brownie', 22000, 'soldout'),
            $this->product(11, 'Milkshake', 29000, 'options', ['new']),
            $this->product(12, 'Cold Coffee', 25000, 'options'),
        ];

        return array_slice($all, $offset, $count);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function combos(): array
    {
        return [
            array_merge($this->product(101, 'Family Feast', 189000, 'combo', ['hot'], '2 large pizzas, wings and a 1.5 L drink.')),
            array_merge($this->product(102, 'Date Night Duo', 129000, 'combo', [], '1 medium pizza, pasta and 2 drinks.')),
            array_merge($this->product(103, 'Gift Box', 149000, 'combo', ['new'], 'A boxed pizza, dessert and a greeting card.')),
        ];
    }
}

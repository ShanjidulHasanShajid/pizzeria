<?php

declare(strict_types=1);

namespace App\Modules\Shared\Presentation\Support;

use Illuminate\Support\Str;

/**
 * TEMPORARY fake data for the Phase 4 static pages.
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
     * The 7 fixed navbar items. `patterns` decides when an item looks active.
     *
     * @return array<int, array{key: string, label: string, route: string, patterns: array<int, string>}>
     */
    public function navItems(): array
    {
        return [
            ['key' => 'home', 'label' => 'Home', 'route' => 'home', 'patterns' => ['home']],
            ['key' => 'menu', 'label' => 'Menu', 'route' => 'menu.index', 'patterns' => ['menu.*', 'categories.*', 'products.*']],
            ['key' => 'builder', 'label' => 'Customized Pizza', 'route' => 'builder.show', 'patterns' => ['builder.*']],
            ['key' => 'gift', 'label' => 'Gift Box', 'route' => 'gift-boxes.index', 'patterns' => ['gift-boxes.*']],
            ['key' => 'story', 'label' => 'Our Story', 'route' => 'about', 'patterns' => ['about']],
            ['key' => 'location', 'label' => 'Location', 'route' => 'locations.index', 'patterns' => ['locations.*']],
            ['key' => 'contact', 'label' => 'Contact Us', 'route' => 'contact', 'patterns' => ['contact']],
        ];
    }

    /**
     * Top-level categories with sub-categories, for the mega menu and the mobile drawer.
     *
     * @return array<int, array{name: string, slug: string, children: array<int, array{name: string, slug: string}>}>
     */
    public function menuGroups(): array
    {
        $groups = [
            'Pizza' => ['Classic', 'Signature', 'Vegetarian', 'Spicy'],
            'Pies' => ['Chicken pies', 'Beef pies'],
            'Sides' => ['Garlic bread', 'Wings', 'Fries'],
            'Pasta' => ['Baked pasta', 'Creamy pasta'],
            'Burgers & Sandwiches' => ['Burgers', 'Sandwiches'],
            'Salads' => [],
            'Desserts' => ['Cakes', 'Brownies'],
            'Drinks' => ['Soft drinks', 'Milkshakes', 'Coffee'],
            'Meal Deals' => [],
        ];

        $result = [];

        foreach ($groups as $name => $children) {
            $result[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'children' => array_map(
                    fn (string $child): array => ['name' => $child, 'slug' => Str::slug($child)],
                    $children,
                ),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $badges
     * @return array{id: int, name: string, slug: string, description: string, price: int, state: string, badges: array<int, string>, image: string}
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
            'badges' => $badges, // new | hot | veg | spicy
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

    /**
     * @return array<int, array{eyebrow: string, title: string, text: string, cta: string, url: string, image: string}>
     */
    public function heroSlides(): array
    {
        return [
            ['eyebrow' => 'Fresh from the oven', 'title' => 'Hand-stretched pizza, delivered hot', 'text' => 'Order online and enjoy it in 40 minutes or less.', 'cta' => 'Order now', 'url' => route('menu.index'), 'image' => $this->image()],
            ['eyebrow' => 'Build your own', 'title' => 'Your pizza, your toppings', 'text' => 'Pick the size, crust and toppings and watch the price update live.', 'cta' => 'Start building', 'url' => route('builder.show'), 'image' => $this->image()],
            ['eyebrow' => 'Meal deals', 'title' => 'Feed everyone for less', 'text' => 'Combos for two, for the family and for the whole office.', 'cta' => 'See deals', 'url' => route('meal-deals.index'), 'image' => $this->image()],
        ];
    }

    /**
     * @return array<int, array{icon: string, title: string, text: string}>
     */
    public function trustBadges(): array
    {
        return [
            ['icon' => 'fire', 'title' => 'Freshly baked', 'text' => 'Made to order, never reheated'],
            ['icon' => 'clock', 'title' => 'Fast delivery', 'text' => 'Hot at your door in about 40 minutes'],
            ['icon' => 'shield-check', 'title' => 'Secure payment', 'text' => 'Cash on delivery or pay online'],
            ['icon' => 'check-circle', 'title' => 'Quality ingredients', 'text' => 'No preservatives, ever'],
        ];
    }

    /**
     * @return array<int, array{title: string, text: string, cta: string, url: string, tone: string}>
     */
    public function promoBanners(): array
    {
        return [
            ['title' => 'Free garlic bread', 'text' => 'On every large pizza this weekend.', 'cta' => 'Order now', 'url' => route('menu.index'), 'tone' => 'brand'],
            ['title' => '20% off pasta', 'text' => 'Every Tuesday, all day.', 'cta' => 'See pasta', 'url' => route('categories.show', 'pasta'), 'tone' => 'accent'],
        ];
    }

    /**
     * @return array<string, array{label: string, products: array<int, array<string, mixed>>}>
     */
    public function showcaseTabs(): array
    {
        return [
            'pizza' => ['label' => 'Pizza', 'products' => $this->products(4, 0)],
            'sides' => ['label' => 'Sides', 'products' => $this->products(4, 3)],
            'desserts' => ['label' => 'Desserts', 'products' => $this->products(4, 8)],
        ];
    }

    /**
     * @return array<int, array{name: string, slug: string, products: array<int, array<string, mixed>>}>
     */
    public function featuredCategories(): array
    {
        return [
            ['name' => 'Pizza', 'slug' => 'pizza', 'products' => $this->products(3, 0)],
            ['name' => 'Pasta & Pies', 'slug' => 'pasta', 'products' => $this->products(3, 5)],
            ['name' => 'Burgers & Sandwiches', 'slug' => 'burgers-sandwiches', 'products' => $this->products(3, 7)],
        ];
    }

    /**
     * @return array<int, array{name: string, address: string, phone: string}>
     */
    public function branches(): array
    {
        return [
            ['name' => 'Gulshan', 'address' => 'House 12, Road 45, Gulshan 2, Dhaka', 'phone' => '01700-000001'],
            ['name' => 'Dhanmondi', 'address' => 'House 8, Road 27, Dhanmondi, Dhaka', 'phone' => '01700-000002'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function openingHours(): array
    {
        return [
            'Saturday to Thursday' => '11:00 AM to 11:00 PM',
            'Friday' => '3:00 PM to 11:30 PM',
        ];
    }

    /**
     * @return array<int, array{src: string, alt: string}>
     */
    public function galleryImages(): array
    {
        $alts = ['Pizza coming out of the oven', 'Our dining area', 'Fresh basil and tomatoes', 'A family table', 'Dessert platter', 'Chef stretching dough'];

        return array_map(fn (string $alt): array => ['src' => $this->image(), 'alt' => $alt], $alts);
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function socialLinks(): array
    {
        return [
            ['label' => 'Facebook', 'url' => '#'],
            ['label' => 'Instagram', 'url' => '#'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Content\Domain\Enums;

/**
 * The tabs of the settings screen.
 */
enum SettingGroup: string
{
    case General = 'general';
    case Contact = 'contact';
    case Social = 'social';
    case Commerce = 'commerce';
    case Home = 'home';
    case Delivery = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General',
            self::Contact => 'Contact',
            self::Social => 'Social media',
            self::Commerce => 'Prices and orders',
            self::Home => 'Home page',
            self::Delivery => 'Delivery',
        };
    }

    /**
     * @return list<SettingKey>
     */
    public function keys(): array
    {
        return array_values(array_filter(
            SettingKey::cases(),
            fn (SettingKey $key): bool => $key->group() === $this,
        ));
    }
}

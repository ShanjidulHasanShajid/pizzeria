<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Staff',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super admin',
        };
    }

    public function isStaffOrAbove(): bool
    {
        return $this !== self::Customer;
    }

    public function isAdminOrAbove(): bool
    {
        return $this === self::Admin || $this === self::SuperAdmin;
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    /**
     * The one place that says who may do what (docs/business-rules.md section 2).
     */
    public function can(Ability $ability): bool
    {
        return match ($ability) {
            Ability::AccessAdmin,
            Ability::ManageOrders => $this->isStaffOrAbove(),

            Ability::AccessAdminSections,
            Ability::ManageCatalog,
            Ability::ManageContent,
            Ability::ManageSales,
            Ability::ManageSettings,
            Ability::ManageCustomers => $this->isAdminOrAbove(),

            Ability::ManageAdminUsers,
            Ability::ForceDelete => $this->isSuperAdmin(),
        };
    }
}

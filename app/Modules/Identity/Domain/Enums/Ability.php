<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

/**
 * Everything a person can be allowed to do in the admin area.
 * The value is the gate name, so Blade can write @can('manage-catalog').
 */
enum Ability: string
{
    // Doors into the admin area
    case AccessAdmin = 'access-admin';                 // staff, admin, super admin
    case AccessAdminSections = 'access-admin-sections'; // admin, super admin (everything except orders)

    // Sections
    case ManageOrders = 'manage-orders';
    case ManageCatalog = 'manage-catalog';
    case ManageContent = 'manage-content';  // also reviews and messages
    case ManageSales = 'manage-sales';      // coupons, delivery zones
    case ManageSettings = 'manage-settings';
    case ManageCustomers = 'manage-customers';

    // Only super admins
    case ManageAdminUsers = 'manage-admin-users';
    case ForceDelete = 'force-delete';
}

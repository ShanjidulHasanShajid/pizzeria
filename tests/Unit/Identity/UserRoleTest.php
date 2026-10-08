<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Enums\Ability;
use App\Modules\Identity\Domain\Enums\UserRole;

$expected = [
    'customer' => [],
    'staff' => [Ability::AccessAdmin, Ability::ManageOrders],
    'admin' => [
        Ability::AccessAdmin, Ability::ManageOrders, Ability::AccessAdminSections, Ability::ManageCatalog,
        Ability::ManageContent, Ability::ManageSales, Ability::ManageSettings, Ability::ManageCustomers,
    ],
    'super_admin' => Ability::cases(),
];

foreach (UserRole::cases() as $role) {
    it("gives {$role->value} exactly the abilities in the business rules", function () use ($role, $expected): void {
        $granted = array_values(array_filter(Ability::cases(), fn (Ability $ability): bool => $role->can($ability)));

        expect($granted)->toEqualCanonicalizing($expected[$role->value]);
    });
}

it('knows which roles are staff, admin and super admin', function (): void {
    expect(UserRole::Customer->isStaffOrAbove())->toBeFalse()
        ->and(UserRole::Staff->isStaffOrAbove())->toBeTrue()
        ->and(UserRole::Staff->isAdminOrAbove())->toBeFalse()
        ->and(UserRole::Admin->isAdminOrAbove())->toBeTrue()
        ->and(UserRole::Admin->isSuperAdmin())->toBeFalse()
        ->and(UserRole::SuperAdmin->isSuperAdmin())->toBeTrue();
});

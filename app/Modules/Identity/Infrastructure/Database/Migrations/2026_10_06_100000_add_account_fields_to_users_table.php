<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Normalized 01XXXXXXXXX (11 digits). Unique, but optional for staff.
            $table->string('phone', 11)->nullable()->unique()->after('email');
            $table->string('role', 20)->default('customer')->index()->after('password');
            $table->boolean('is_blocked')->default(false)->index()->after('role');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_blocked']);
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'role', 'is_blocked']);
        });
    }
};

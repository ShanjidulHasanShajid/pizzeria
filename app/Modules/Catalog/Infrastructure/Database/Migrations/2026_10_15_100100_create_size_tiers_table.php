<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('size_tiers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            // Unique including soft-deleted rows: a trashed size still owns its slug.
            $table->string('slug', 120)->unique();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('size_tiers');
    }
};

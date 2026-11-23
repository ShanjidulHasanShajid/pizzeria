<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_group_id')->constrained()->cascadeOnDelete();
            // Unique per group, but enforced by the OptionGroup aggregate:
            // soft-deleted options keep their rows and their names.
            $table->string('name', 100);
            $table->string('image_path')->nullable();
            $table->boolean('is_veg')->default(false);
            $table->boolean('is_available')->default(true);
            // flat or per_size (PriceMode enum).
            $table->string('price_mode', 20);
            // Money in poisha. Null for an option that never had a flat price.
            $table->unsignedInteger('flat_price')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};

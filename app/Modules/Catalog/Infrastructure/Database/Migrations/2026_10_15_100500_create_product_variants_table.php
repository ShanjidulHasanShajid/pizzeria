<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_tier_id')->nullable()->constrained()->restrictOnDelete();
            // Unique per product, but enforced by the Product aggregate: a soft-deleted
            // "Large" keeps its row, so a database index would block adding "Large" again.
            $table->string('label', 60);
            // Money in poisha (৳1 = 100).
            $table->unsignedInteger('price');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

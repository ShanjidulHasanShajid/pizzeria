<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_id')->constrained()->cascadeOnDelete();
            // size_tiers belongs to the Catalog module; only the database knows about this link.
            $table->foreignId('size_tier_id')->constrained()->restrictOnDelete();
            // Money in poisha. No row = not offered at that size. Zero = free.
            $table->unsignedInteger('price');
            $table->unique(['option_id', 'size_tier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_prices');
    }
};

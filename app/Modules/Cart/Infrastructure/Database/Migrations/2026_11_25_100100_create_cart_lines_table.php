<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            // CartLineType enum: standard (combo and custom pizza in later phases).
            $table->string('type', 20);
            // A cart is no history: when a product is deleted for good, its lines go too.
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            // Only ids: {"option group id": [option ids]}. Never prices.
            $table->json('selections');
            $table->string('note', 200)->nullable();
            $table->unsignedTinyInteger('quantity');
            // Poisha. What the customer saw last; never used to charge anything.
            $table->unsignedInteger('last_unit_price');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_lines');
    }
};

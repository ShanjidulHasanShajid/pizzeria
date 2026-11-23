<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table): void {
            $table->id();
            // "tab" (product showcase) or "row" (three products).
            $table->string('kind', 10);
            // categories belongs to the Catalog module; deleted forever = gone from the home page.
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            // Picked products of a row, as a list of ids. Products that are gone are simply skipped.
            $table->json('product_ids')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->unique(['kind', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};

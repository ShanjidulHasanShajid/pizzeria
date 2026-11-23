<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_group', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_group_id')->constrained()->restrictOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedTinyInteger('min_select')->default(0);
            $table->unsignedTinyInteger('max_select')->default(1);
            $table->unsignedTinyInteger('free_count')->default(0);
            $table->unsignedInteger('sort')->default(0);
            // A group is attached to a product only once.
            $table->unique(['product_id', 'option_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_group');
    }
};

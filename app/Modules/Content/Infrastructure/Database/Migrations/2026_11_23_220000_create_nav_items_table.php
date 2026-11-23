<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_items', function (Blueprint $table): void {
            $table->id();
            // A value of the NavRoute enum.
            $table->string('route_key', 50)->unique();
            $table->string('label', 30);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_highlighted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_items');
    }
};

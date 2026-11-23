<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('address');
            $table->string('area', 100)->nullable();
            $table->string('phone', 25);
            $table->string('map_embed_url', 1000)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // {"saturday": ["11:00", "23:00"], "friday": null, ...}
            $table->json('opening_hours');
            $table->boolean('is_pickup_available')->default(true);
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

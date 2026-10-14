<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            // standard, builder or combo (ProductType enum).
            $table->string('type', 20)->index();
            $table->string('name', 150);
            $table->string('slug', 120)->unique();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            // made_to_order, same_day or next_day (LeadTimeClass enum) plus minutes (at most 7 days).
            $table->string('lead_time_class', 20);
            $table->unsignedSmallInteger('lead_time_minutes');
            $table->boolean('is_new')->default(false);
            $table->boolean('is_hot')->default(false)->index();
            $table->boolean('is_giftable')->default(false);
            $table->boolean('is_published')->default(false)->index();
            $table->boolean('is_available')->default(true)->index();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

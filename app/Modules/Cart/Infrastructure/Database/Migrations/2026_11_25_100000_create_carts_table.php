<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table): void {
            $table->id();
            // Exactly one of the two is set (CartOwner). Unique: one cart per owner.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('guest_token', 40)->nullable()->unique();
            $table->timestamps();
            // For deleting old guest carts (11.22).
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

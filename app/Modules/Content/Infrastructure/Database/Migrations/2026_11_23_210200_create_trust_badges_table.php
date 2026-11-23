<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trust_badges', function (Blueprint $table): void {
            $table->id();
            $table->string('icon', 50);
            $table->string('title', 60);
            $table->string('text', 120)->nullable();
            $table->unsignedInteger('sort')->default(0)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_badges');
    }
};

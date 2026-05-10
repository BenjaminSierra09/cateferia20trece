<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('beverage_size_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beverage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('size_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['beverage_id', 'size_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beverage_size_prices');
    }
};

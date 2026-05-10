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
        Schema::create('customization_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customization_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });

        Schema::create('beverage_customization_option', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beverage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customization_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['beverage_id', 'customization_option_id'], 'beverage_customization_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beverage_customization_option');
        Schema::dropIfExists('customization_options');
    }
};

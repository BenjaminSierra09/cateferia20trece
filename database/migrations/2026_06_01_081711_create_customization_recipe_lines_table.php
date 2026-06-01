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
        Schema::create('customization_recipe_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customization_type_id')->constrained()->cascadeOnDelete();
            // NULL option = default consumption for every option of the category.
            $table->foreignId('customization_option_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->index(['customization_type_id', 'customization_option_id', 'inventory_item_id'], 'customization_recipe_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customization_recipe_lines');
    }
};

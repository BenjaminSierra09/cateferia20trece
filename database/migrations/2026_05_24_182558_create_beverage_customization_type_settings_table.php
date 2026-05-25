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
        Schema::create('beverage_customization_type_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('beverage_id')->constrained(indexName: 'bcts_beverage_id_fk')->cascadeOnDelete();
            $table->foreignId('customization_type_id')->constrained(table: 'customization_types', indexName: 'bcts_type_id_fk')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_open_by_default')->default(false);
            $table->timestamps();

            $table->unique(['beverage_id', 'customization_type_id'], 'beverage_customization_type_setting_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beverage_customization_type_settings');
    }
};

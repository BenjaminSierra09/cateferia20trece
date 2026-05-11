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
            $table->unsignedBigInteger('customization_type_id');
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->foreign('customization_type_id', 'co_type_fk')
                ->references('id')
                ->on('customization_types')
                ->onDelete('cascade');
        });

        Schema::create('beverage_customization_option', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('beverage_id');
            $table->unsignedBigInteger('customization_option_id');
            $table->timestamps();

            $table->foreign('beverage_id', 'bco_beverage_fk')
                ->references('id')
                ->on('beverages')
                ->onDelete('cascade');
            
            $table->foreign('customization_option_id', 'bco_option_fk')
                ->references('id')
                ->on('customization_options')
                ->onDelete('cascade');

            $table->unique(['beverage_id', 'customization_option_id'], 'bco_unique');
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

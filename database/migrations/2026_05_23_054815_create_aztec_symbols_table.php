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
        Schema::create('aztec_symbols', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('sort_order')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('spanish_name')->nullable();
            $table->string('deity')->nullable();
            $table->string('body_area')->nullable();
            $table->text('meaning')->nullable();
            $table->text('service_description')->nullable();
            $table->text('customer_greeting')->nullable();
            $table->text('taste_profile')->nullable();
            $table->json('recommended_items')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('name');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aztec_symbols');
    }
};

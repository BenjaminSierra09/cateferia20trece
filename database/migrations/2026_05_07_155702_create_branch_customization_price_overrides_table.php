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
        Schema::create('branch_customization_price_overrides', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('customization_option_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('branch_id', 'bcpo_branch_fk')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');
            
            $table->foreign('customization_option_id', 'bcpo_option_fk')
                ->references('id')
                ->on('customization_options')
                ->onDelete('cascade');

            $table->unique(['branch_id', 'customization_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_customization_price_overrides');
    }
};

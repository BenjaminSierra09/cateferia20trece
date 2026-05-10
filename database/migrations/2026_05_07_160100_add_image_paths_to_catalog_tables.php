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
        Schema::table('beverage_categories', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('customization_types', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('selection_mode');
        });

        Schema::table('customization_options', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customization_options', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });

        Schema::table('customization_types', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });

        Schema::table('beverage_categories', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};

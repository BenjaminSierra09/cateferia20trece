<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customization_option_size_prices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customization_option_id');
            $table->unsignedBigInteger('size_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('customization_option_id', 'cosp_option_fk')
                ->references('id')
                ->on('customization_options')
                ->onDelete('cascade');

            $table->foreign('size_id', 'cosp_size_fk')
                ->references('id')
                ->on('sizes')
                ->onDelete('cascade');

            $table->unique(['customization_option_id', 'size_id'], 'cosp_option_size_unique');
        });

        $now = now();
        $sizes = DB::table('sizes')->pluck('id');

        DB::table('customization_options')
            ->select(['id', 'price'])
            ->orderBy('id')
            ->chunk(100, function ($options) use ($sizes, $now): void {
                $rows = [];

                foreach ($options as $option) {
                    foreach ($sizes as $sizeId) {
                        $rows[] = [
                            'customization_option_id' => $option->id,
                            'size_id' => $sizeId,
                            'price' => $option->price,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('customization_option_size_prices')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customization_option_size_prices');
    }
};

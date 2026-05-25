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
        Schema::create('branch_customization_size_price_overrides', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('customization_option_id');
            $table->unsignedBigInteger('size_id');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('branch_id', 'bcspo_branch_fk')
                ->references('id')
                ->on('branches')
                ->onDelete('cascade');

            $table->foreign('customization_option_id', 'bcspo_option_fk')
                ->references('id')
                ->on('customization_options')
                ->onDelete('cascade');

            $table->foreign('size_id', 'bcspo_size_fk')
                ->references('id')
                ->on('sizes')
                ->onDelete('cascade');

            $table->unique(['branch_id', 'customization_option_id', 'size_id'], 'bcspo_unique');
        });

        $now = now();
        $sizes = DB::table('sizes')->pluck('id');

        DB::table('branch_customization_price_overrides')
            ->select(['branch_id', 'customization_option_id', 'price'])
            ->orderBy('id')
            ->chunk(100, function ($overrides) use ($sizes, $now): void {
                $rows = [];

                foreach ($overrides as $override) {
                    foreach ($sizes as $sizeId) {
                        $rows[] = [
                            'branch_id' => $override->branch_id,
                            'customization_option_id' => $override->customization_option_id,
                            'size_id' => $sizeId,
                            'price' => $override->price,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('branch_customization_size_price_overrides')->insertOrIgnore($rows);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_customization_size_price_overrides');
    }
};

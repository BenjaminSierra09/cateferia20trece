<?php

use App\Models\Beverage;
use App\Models\CustomizationType;
use App\Support\BeverageTemperatureCustomization;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $temperatureCustomization = app(BeverageTemperatureCustomization::class);
        $temperatureCustomization->ensureExists();

        Beverage::query()
            ->orderBy('id')
            ->chunkById(100, function ($beverages) use ($temperatureCustomization): void {
                foreach ($beverages as $beverage) {
                    $temperatureCustomization->applyToBeverage(
                        $beverage,
                        (bool) $beverage->is_hot,
                    );
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CustomizationType::query()
            ->where('slug', BeverageTemperatureCustomization::TypeSlug)
            ->delete();
    }
};

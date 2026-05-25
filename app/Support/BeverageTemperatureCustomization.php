<?php

namespace App\Support;

use App\Models\Beverage;
use App\Models\BeverageCustomizationTypeSetting;
use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;
use App\Models\CustomizationType;
use App\Models\Size;

class BeverageTemperatureCustomization
{
    public const TypeSlug = 'temperatura';

    public const HotName = 'Caliente';

    public const ColdName = 'Fría';

    /**
     * @return array{type: CustomizationType, hot: CustomizationOption, cold: CustomizationOption}
     */
    public function ensureExists(): array
    {
        ['type' => $type, 'hot' => $hot, 'cold' => $cold] = CatalogImageManager::withoutQueueing(function (): array {
            $type = CustomizationType::query()->updateOrCreate([
                'slug' => self::TypeSlug,
            ], [
                'name' => 'Temperatura',
                'selection_mode' => 'single',
                'is_active' => true,
            ]);

            $hot = CustomizationOption::query()->updateOrCreate([
                'customization_type_id' => $type->id,
                'name' => self::HotName,
            ], [
                'name' => self::HotName,
                'price' => 0,
                'is_available' => true,
            ]);

            $cold = CustomizationOption::query()->updateOrCreate([
                'customization_type_id' => $type->id,
                'name' => self::ColdName,
            ], [
                'name' => self::ColdName,
                'price' => 0,
                'is_available' => true,
            ]);

            return compact('type', 'hot', 'cold');
        });

        $this->ensureZeroSizePrices($hot);
        $this->ensureZeroSizePrices($cold);

        return compact('type', 'hot', 'cold');
    }

    public function applyToBeverage(Beverage $beverage, ?bool $isHotDefault = null, bool $preserveExistingDefault = false): void
    {
        ['type' => $type, 'hot' => $hot, 'cold' => $cold] = $this->ensureExists();
        $existingDefaultOptionId = $preserveExistingDefault
            ? $beverage->customizationOptions()
                ->whereIn('customization_options.id', [$hot->id, $cold->id])
                ->wherePivot('is_default', true)
                ->value('customization_options.id')
            : null;
        $defaultOptionId = $existingDefaultOptionId
            ?? (($isHotDefault ?? true) ? $hot->id : $cold->id);

        $beverage->customizationOptions()->syncWithoutDetaching([
            $hot->id => ['is_default' => $defaultOptionId === $hot->id],
            $cold->id => ['is_default' => $defaultOptionId === $cold->id],
        ]);

        $beverage->customizationOptions()->updateExistingPivot($hot->id, [
            'is_default' => $defaultOptionId === $hot->id,
        ]);
        $beverage->customizationOptions()->updateExistingPivot($cold->id, [
            'is_default' => $defaultOptionId === $cold->id,
        ]);

        $this->persistFirstSetting($beverage, $type->id);
    }

    /**
     * @param  array<int>  $selectedOptionIds
     * @param  array<int>  $defaultOptionIds
     * @return array{selected: array<int>, defaults: array<int>}
     */
    public function normalizeSelections(array $selectedOptionIds, array $defaultOptionIds, ?bool $isHotDefault = null): array
    {
        ['hot' => $hot, 'cold' => $cold] = $this->ensureExists();
        $temperatureOptionIds = [$hot->id, $cold->id];
        $explicitDefault = collect($defaultOptionIds)
            ->map(fn (mixed $optionId): int => (int) $optionId)
            ->first(fn (int $optionId): bool => in_array($optionId, $temperatureOptionIds, true));

        $defaultTemperatureOptionId = $explicitDefault
            ?? (($isHotDefault ?? true) ? $hot->id : $cold->id);

        return [
            'selected' => collect($selectedOptionIds)
                ->map(fn (mixed $optionId): int => (int) $optionId)
                ->merge($temperatureOptionIds)
                ->unique()
                ->values()
                ->all(),
            'defaults' => collect($defaultOptionIds)
                ->map(fn (mixed $optionId): int => (int) $optionId)
                ->reject(fn (int $optionId): bool => in_array($optionId, $temperatureOptionIds, true))
                ->push($defaultTemperatureOptionId)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int>
     */
    public function optionIds(): array
    {
        ['hot' => $hot, 'cold' => $cold] = $this->ensureExists();

        return [$hot->id, $cold->id];
    }

    public function typeId(): int
    {
        ['type' => $type] = $this->ensureExists();

        return (int) $type->id;
    }

    /**
     * @param  array<int|string, array{sort_order:int, is_open_by_default:bool}>  $settings
     * @return array<int|string, array{sort_order:int, is_open_by_default:bool}>
     */
    public function normalizeSettings(array $settings): array
    {
        $temperatureTypeId = $this->typeId();
        $sortOrder = 1;
        $rebuilt = [
            $temperatureTypeId => [
                'sort_order' => 0,
                'is_open_by_default' => true,
            ],
        ];

        collect($settings)
            ->reject(fn (array $setting, int|string $typeId): bool => (int) $typeId === $temperatureTypeId)
            ->sortBy('sort_order')
            ->each(function (array $setting, int|string $typeId) use (&$rebuilt, &$sortOrder): void {
                $rebuilt[(int) $typeId] = [
                    'sort_order' => $sortOrder,
                    'is_open_by_default' => (bool) ($setting['is_open_by_default'] ?? false),
                ];

                $sortOrder++;
            });

        return $rebuilt;
    }

    private function persistFirstSetting(Beverage $beverage, int $temperatureTypeId): void
    {
        $existingSettings = $beverage->customizationTypeSettings()
            ->get()
            ->keyBy('customization_type_id');

        $otherTypeIds = $existingSettings
            ->where('customization_type_id', '!=', $temperatureTypeId)
            ->sortBy('sort_order')
            ->pluck('customization_type_id')
            ->map(fn (mixed $typeId): int => (int) $typeId)
            ->values();

        BeverageCustomizationTypeSetting::query()->updateOrCreate([
            'beverage_id' => $beverage->id,
            'customization_type_id' => $temperatureTypeId,
        ], [
            'sort_order' => 0,
            'is_open_by_default' => true,
        ]);

        $otherTypeIds->each(function (int $typeId, int $index) use ($beverage, $existingSettings): void {
            BeverageCustomizationTypeSetting::query()->updateOrCreate([
                'beverage_id' => $beverage->id,
                'customization_type_id' => $typeId,
            ], [
                'sort_order' => $index + 1,
                'is_open_by_default' => (bool) ($existingSettings->get($typeId)?->is_open_by_default ?? false),
            ]);
        });
    }

    private function ensureZeroSizePrices(CustomizationOption $option): void
    {
        Size::query()
            ->where('is_active', true)
            ->get()
            ->each(fn (Size $size) => CustomizationOptionSizePrice::query()->firstOrCreate([
                'customization_option_id' => $option->id,
                'size_id' => $size->id,
            ], [
                'price' => 0,
            ]));
    }
}

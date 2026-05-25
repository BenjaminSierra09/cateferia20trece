<?php

namespace App\Support;

use App\Models\BranchCustomizationPriceOverride;
use App\Models\BranchCustomizationSizePriceOverride;
use App\Models\CustomizationOption;
use App\Models\CustomizationOptionSizePrice;

class CustomizationPriceResolver
{
    public function resolve(CustomizationOption $option, ?int $sizeId = null, ?int $branchId = null): float
    {
        if ($sizeId !== null && $branchId !== null) {
            $price = $this->loadedBranchSizeOverride($option, $branchId, $sizeId)
                ?? BranchCustomizationSizePriceOverride::query()
                    ->where('branch_id', $branchId)
                    ->where('customization_option_id', $option->id)
                    ->where('size_id', $sizeId)
                    ->value('price');

            if ($price !== null) {
                return round((float) $price, 2);
            }
        }

        if ($sizeId !== null) {
            $price = $this->loadedSizePrice($option, $sizeId)
                ?? CustomizationOptionSizePrice::query()
                    ->where('customization_option_id', $option->id)
                    ->where('size_id', $sizeId)
                    ->value('price');

            if ($price !== null) {
                return round((float) $price, 2);
            }
        }

        if ($branchId !== null) {
            $price = BranchCustomizationPriceOverride::query()
                ->where('branch_id', $branchId)
                ->where('customization_option_id', $option->id)
                ->value('price');

            if ($price !== null) {
                return round((float) $price, 2);
            }
        }

        return round((float) $option->price, 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sizePriceRows(CustomizationOption $option, ?int $branchId = null): array
    {
        $sizePrices = $option->relationLoaded('sizePrices')
            ? $option->sizePrices
            : $option->sizePrices()->with('size')->get();

        return $sizePrices
            ->sortBy(fn (CustomizationOptionSizePrice $sizePrice): string => sprintf(
                '%010.2f-%s',
                (float) ($sizePrice->size?->capacity_ounces ?? 0),
                $sizePrice->size?->name ?? '',
            ))
            ->map(fn (CustomizationOptionSizePrice $sizePrice): array => [
                'size_id' => $sizePrice->size_id,
                'size_name' => $sizePrice->size?->name,
                'capacity_label' => $sizePrice->size?->capacity_label,
                'base_price' => round((float) $sizePrice->price, 2),
                'price' => $this->resolve($option, (int) $sizePrice->size_id, $branchId),
            ])
            ->values()
            ->all();
    }

    private function loadedSizePrice(CustomizationOption $option, int $sizeId): mixed
    {
        if (! $option->relationLoaded('sizePrices')) {
            return null;
        }

        return $option->sizePrices->firstWhere('size_id', $sizeId)?->price;
    }

    private function loadedBranchSizeOverride(CustomizationOption $option, int $branchId, int $sizeId): mixed
    {
        if (! $option->relationLoaded('branchSizePriceOverrides')) {
            return null;
        }

        return $option->branchSizePriceOverrides
            ->where('branch_id', $branchId)
            ->firstWhere('size_id', $sizeId)
            ?->price;
    }
}

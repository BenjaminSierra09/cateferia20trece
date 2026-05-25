<?php

namespace App\Http\Resources;

use App\Models\Beverage;
use App\Models\BeverageSizePrice;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/** @mixin Beverage */
class BeverageResource extends JsonResource
{
    protected function resolveImageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $storageUrl = Storage::url($this->image_path);

        if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
            return $storageUrl;
        }

        return url($storageUrl);
    }

    /**
     * @return Collection<int, BeverageSizePrice>
     */
    protected function availableSizePricesFor(Request $request): Collection
    {
        $sizePrices = $this->sizePrices
            ->filter(fn (BeverageSizePrice $sizePrice): bool => $sizePrice->is_active && (bool) ($sizePrice->size?->is_active ?? true));

        $branchId = $request->integer('branch_id');

        if ($branchId < 1) {
            return $sizePrices->values();
        }

        $blockedSizeIds = $this->relationLoaded('branchSizeAvailabilities')
            ? $this->branchSizeAvailabilities
                ->where('branch_id', $branchId)
                ->where('is_available', false)
                ->pluck('size_id')
                ->map(fn (mixed $sizeId): int => (int) $sizeId)
                ->all()
            : [];

        return $sizePrices
            ->reject(fn (BeverageSizePrice $sizePrice): bool => in_array((int) $sizePrice->size_id, $blockedSizeIds, true))
            ->values();
    }

    /**
     * @return Collection<int, CustomizationOption>
     */
    protected function orderedCustomizationOptions(): Collection
    {
        $settings = $this->relationLoaded('customizationTypeSettings')
            ? $this->customizationTypeSettings->keyBy('customization_type_id')
            : collect();

        return $this->customizationOptions
            ->each(function (CustomizationOption $option) use ($settings): void {
                $setting = $settings->get($option->customization_type_id);

                if ($option->type instanceof CustomizationType) {
                    $option->type->setAttribute('sort_order', $setting?->sort_order);
                    $option->type->setAttribute('is_open_by_default', (bool) ($setting?->is_open_by_default ?? false));
                }
            })
            ->sortBy(fn (CustomizationOption $option): string => sprintf(
                '%010d-%s-%s',
                (int) ($settings->get($option->customization_type_id)?->sort_order ?? PHP_INT_MAX),
                $option->type?->name ?? '',
                $option->name,
            ))
            ->values();
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'beverage_category_id' => $this->beverage_category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->resolveImageUrl(),
            'base_price' => $this->base_price,
            'is_active' => $this->is_active,
            'popularity_quantity' => (int) ($this->popularity_quantity ?? 0),
            'category' => $this->whenLoaded('category', fn () => new BeverageCategoryResource($this->category)),
            'sizes' => $this->whenLoaded('sizePrices', fn () => BeverageSizePriceResource::collection($this->availableSizePricesFor($request))),
            'customizations' => $this->whenLoaded('customizationOptions', fn () => CustomizationOptionResource::collection($this->orderedCustomizationOptions())),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

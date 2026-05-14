<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Support\CatalogImageManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CustomerFavoriteBeverageService
{
    /**
     * Resolve the customer's top favorite beverages with preferred size and frequent customizations.
     *
     * @return Collection<int, array{
     *     beverage_id:int,
     *     beverage_name:string,
     *     beverage_image_url:?string,
     *     total_quantity:int,
     *     line_count:int,
     *     last_ordered_at:?string,
     *     top_size:?array{size_id:int,size_name:string,capacity_label:?string,total_quantity:int},
     *     frequent_customizations:list<array{customization_option_id:?int,type:?string,name:string,selection_count:int}>
     * }>
     */
    public function topForCustomer(Customer $customer, int $limit = 3): Collection
    {
        $topBeverages = SaleItem::query()
            ->selectRaw('sale_items.beverage_id, SUM(sale_items.quantity) as total_quantity, COUNT(*) as line_count, MAX(sales.sold_at) as last_ordered_at')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.customer_id', $customer->id)
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereNotNull('sale_items.beverage_id')
            ->groupBy('sale_items.beverage_id')
            ->orderByDesc('total_quantity')
            ->orderByDesc('last_ordered_at')
            ->limit($limit)
            ->get();

        if ($topBeverages->isEmpty()) {
            return collect();
        }

        $favoriteBeverageIds = $topBeverages
            ->pluck('beverage_id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $itemsByBeverage = SaleItem::query()
            ->with(['beverage', 'size', 'customizations'])
            ->whereIn('beverage_id', $favoriteBeverageIds)
            ->whereHas('sale', function ($query) use ($customer): void {
                $query
                    ->where('customer_id', $customer->id)
                    ->where('status', SaleStatus::Completed->value);
            })
            ->get()
            ->groupBy('beverage_id');

        return $topBeverages->map(function (object $favoriteBeverage) use ($itemsByBeverage): array {
            $beverageId = (int) $favoriteBeverage->beverage_id;
            $saleItems = $itemsByBeverage->get($beverageId, collect());
            $beverage = $saleItems->first()?->beverage;

            $topSize = $saleItems
                ->filter(fn (SaleItem $saleItem): bool => $saleItem->size !== null)
                ->groupBy('size_id')
                ->map(function (Collection $sizeItems): array {
                    /** @var SaleItem $firstItem */
                    $firstItem = $sizeItems->first();

                    return [
                        'size_id' => (int) $firstItem->size_id,
                        'size_name' => $firstItem->size?->name ?? 'Sin tamaño',
                        'capacity_label' => $firstItem->size?->capacity_label,
                        'total_quantity' => (int) $sizeItems->sum('quantity'),
                    ];
                })
                ->sortByDesc('total_quantity')
                ->values()
                ->first();

            $frequentCustomizations = $saleItems
                ->flatMap(function (SaleItem $saleItem): Collection {
                    return $saleItem->customizations->map(function ($customization) use ($saleItem): array {
                        $selectionCount = max(1, (int) $saleItem->quantity) * max(1, (int) $customization->quantity);

                        return [
                            'group_key' => $customization->customization_option_id !== null
                                ? 'option-'.$customization->customization_option_id
                                : 'special-'.strtolower((string) $customization->customization_type_name).'-'.strtolower($customization->customization_name),
                            'customization_option_id' => $customization->customization_option_id,
                            'type' => $customization->customization_type_name,
                            'name' => $customization->customization_name,
                            'selection_count' => $selectionCount,
                        ];
                    });
                })
                ->groupBy('group_key')
                ->map(function (Collection $customizationSelections): array {
                    /** @var array{customization_option_id:?int,type:?string,name:string,selection_count:int} $firstSelection */
                    $firstSelection = $customizationSelections->first();

                    return [
                        'customization_option_id' => $firstSelection['customization_option_id'],
                        'type' => $firstSelection['type'],
                        'name' => $firstSelection['name'],
                        'selection_count' => (int) $customizationSelections->sum('selection_count'),
                    ];
                })
                ->sortByDesc('selection_count')
                ->values()
                ->take(3)
                ->all();

            return [
                'beverage_id' => $beverageId,
                'beverage_name' => $beverage?->name ?? 'Bebida',
                'beverage_image_url' => $this->resolveImageUrl($beverage?->image_path),
                'total_quantity' => (int) $favoriteBeverage->total_quantity,
                'line_count' => (int) $favoriteBeverage->line_count,
                'last_ordered_at' => $favoriteBeverage->last_ordered_at !== null
                    ? Carbon::parse($favoriteBeverage->last_ordered_at)->toIso8601String()
                    : null,
                'top_size' => $topSize,
                'frequent_customizations' => $frequentCustomizations,
            ];
        })->values();
    }

    protected function resolveImageUrl(?string $imagePath): ?string
    {
        return CatalogImageManager::publicUrl($imagePath);
    }
}

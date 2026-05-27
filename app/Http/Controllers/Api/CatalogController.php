<?php

namespace App\Http\Controllers\Api;

use App\Enums\SaleStatus;
use App\Http\Resources\BeverageResource;
use App\Models\Beverage;
use App\Models\Branch;
use App\Models\CustomizationType;
use App\Models\Size;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Display the mobile catalog payload.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $branchId = $request->integer('branch_id') ?: null;

        return response()->json([
            'branches' => Branch::query()
                ->where('is_active', true)
                ->get([
                    'id',
                    'name',
                    'city',
                    'mercado_pago_is_active',
                    'mercado_pago_access_token',
                    'mercado_pago_default_terminal_id',
                    'mercado_pago_default_terminal_name',
                ])
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'city' => $branch->city,
                    'mercado_pago_enabled' => $branch->mercado_pago_is_active && filled($branch->mercado_pago_access_token),
                    'mercado_pago_default_terminal_id' => $branch->mercado_pago_default_terminal_id,
                    'mercado_pago_default_terminal_name' => $branch->mercado_pago_default_terminal_name,
                ]),
            'sizes' => Size::query()->where('is_active', true)->get(['id', 'name', 'capacity_label']),
            'customization_types' => CustomizationType::query()
                ->with(['options' => fn ($query) => $query
                    ->where('is_available', true)
                    ->with([
                        'sizePrices.size',
                        'branchSizePriceOverrides' => fn ($overrideQuery) => $overrideQuery->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)),
                    ])])
                ->where('is_active', true)
                ->get(),
            'beverages' => BeverageResource::collection(
                Beverage::query()
                    ->with([
                        'category',
                        'sizePrices.size',
                        'customizationOptions.type',
                        'customizationOptions.sizePrices.size',
                        'customizationOptions.branchSizePriceOverrides' => fn ($query) => $query->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)),
                        'customizationTypeSettings',
                        'branchSizeAvailabilities' => fn ($query) => $query->when($branchId !== null, fn ($branchQuery) => $branchQuery->where('branch_id', $branchId)),
                    ])
                    ->withSum([
                        'saleItems as popularity_quantity' => fn ($query) => $query->whereHas(
                            'sale',
                            fn ($saleQuery) => $saleQuery->where('status', SaleStatus::Completed),
                        ),
                    ], 'quantity')
                    ->where('is_active', true)
                    ->orderByDesc('popularity_quantity')
                    ->orderBy('name')
                    ->get(),
            ),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Beverage;
use App\Models\BranchCustomizationPriceOverride;
use App\Models\Customer;
use App\Models\CustomizationOption;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Size;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        protected RewardProgramService $rewardProgramService,
    ) {}

    /**
     * Register a sale from structured payload data.
     *
     * @param  array<string, mixed>  $payload
     */
    public function register(array $payload, User $user, WorkSession $workSession): Sale
    {
        return DB::transaction(function () use ($payload, $user, $workSession): Sale {
            $customer = isset($payload['customer_id'])
                ? Customer::query()->find($payload['customer_id'])
                : null;

            $lineItems = collect(Arr::wrap($payload['items'] ?? []))
                ->map(fn (array $item): array => $this->resolveLineItem($item, $workSession))
                ->values();

            if ($lineItems->isEmpty()) {
                throw new InvalidArgumentException('La venta requiere al menos un producto.');
            }

            $subtotal = round($lineItems->sum('line_total'), 2);
            $discountTotal = round((float) ($payload['discount_total'] ?? 0), 2);
            $rewardRedeemedTotal = $customer !== null
                ? min(round((float) ($payload['reward_redeemed_total'] ?? 0), 2), (float) $customer->reward_balance)
                : 0;
            $total = max(round($subtotal - $discountTotal - $rewardRedeemedTotal, 2), 0);

            $sale = Sale::create([
                'branch_id' => $workSession->branch_id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'work_session_id' => $workSession->id,
                'sold_at' => now(),
                'payment_method' => $payload['payment_method'],
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'reward_redeemed_total' => $rewardRedeemedTotal,
                'total' => $total,
                'discount_concept' => $payload['discount_concept'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($lineItems as $lineItem) {
                /** @var SaleItem $saleItem */
                $saleItem = $sale->items()->create(Arr::except($lineItem, ['customizations']));

                foreach ($lineItem['customizations'] as $customization) {
                    $saleItem->customizations()->create($customization);
                }
            }

            if ($customer !== null && $rewardRedeemedTotal > 0) {
                $this->rewardProgramService->redeem($customer, $sale, $rewardRedeemedTotal);
            }

            if ($customer !== null) {
                $this->rewardProgramService->applyEarnedRewards($customer, $sale);
            }

            return $sale->load('branch', 'user', 'customer', 'items.customizations');
        });
    }

    /**
     * Resolve an item payload into persisted sale values.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function resolveLineItem(array $item, WorkSession $workSession): array
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $specialInstructions = $item['special_instructions'] ?? null;
        $customizations = [];

        if (! empty($item['beverage_id']) && ! empty($item['size_id'])) {
            $beverage = Beverage::query()
                ->with(['sizePrices.size', 'customizationOptions.type'])
                ->findOrFail($item['beverage_id']);
            $size = Size::query()->findOrFail($item['size_id']);

            $branchOverride = DB::table('branch_beverage_price_overrides')
                ->where('branch_id', $workSession->branch_id)
                ->where('beverage_id', $beverage->id)
                ->where('size_id', $size->id)
                ->value('price');

            $basePrice = $branchOverride
                ?? $beverage->sizePrices()->where('size_id', $size->id)->value('price')
                ?? $beverage->base_price
                ?? 0;

            foreach (Arr::wrap($item['customization_option_ids'] ?? []) as $customizationOptionId) {
                $option = CustomizationOption::query()->with('type')->findOrFail($customizationOptionId);
                $override = BranchCustomizationPriceOverride::query()
                    ->where('branch_id', $workSession->branch_id)
                    ->where('customization_option_id', $option->id)
                    ->value('price');

                $customizations[] = [
                    'customization_option_id' => $option->id,
                    'customization_type_name' => $option->type?->name,
                    'customization_name' => $option->name,
                    'quantity' => 1,
                    'price' => $override ?? $option->price,
                ];
            }

            if (! empty($item['special_customization_name'])) {
                $customizations[] = [
                    'customization_option_id' => null,
                    'customization_type_name' => 'Especial',
                    'customization_name' => $item['special_customization_name'],
                    'quantity' => 1,
                    'price' => round((float) ($item['special_customization_price'] ?? 0), 2),
                ];
            }

            $customizationTotal = collect($customizations)->sum(fn (array $customization): float => (float) $customization['price']);
            $unitPrice = round((float) $basePrice + $customizationTotal, 2);

            return [
                'beverage_id' => $beverage->id,
                'product_id' => null,
                'size_id' => $size->id,
                'item_name' => $beverage->name.' '.$size->name,
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
                'special_instructions' => $specialInstructions,
                'customizations' => $customizations,
            ];
        }

        if (! empty($item['product_id'])) {
            $product = Product::query()->findOrFail($item['product_id']);
            $unitPrice = round((float) $product->base_price, 2);

            return [
                'beverage_id' => null,
                'product_id' => $product->id,
                'size_id' => null,
                'item_name' => $product->unit_type === 'gram'
                    ? sprintf('%s (%dg)', $product->name, $quantity)
                    : $product->name,
                'quantity' => $quantity,
                'base_price' => $unitPrice,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
                'special_instructions' => $specialInstructions,
                'customizations' => [],
            ];
        }

        $itemName = trim((string) ($item['item_name'] ?? 'Producto general'));
        $basePrice = round((float) ($item['unit_price'] ?? 0), 2);

        return [
            'beverage_id' => null,
            'product_id' => null,
            'size_id' => null,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'base_price' => $basePrice,
            'unit_price' => $basePrice,
            'line_total' => round($basePrice * $quantity, 2),
            'special_instructions' => $specialInstructions,
            'customizations' => [],
        ];
    }
}

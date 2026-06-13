<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use App\Services\WorkSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = Sale::query()
            ->with([
                'branch',
                'user',
                'customer.qrCodes',
                'items.size',
                'items.beverage',
                'items.product',
                'items.customizations',
                'mercadoPagoPointOrder',
            ])
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->user()?->hasLimitedAccountingView(), fn ($query) => $query->whereNotIn('payment_method', [
                PaymentMethod::Cash->value,
                PaymentMethod::Mixed->value,
            ]))
            ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->string('payment_method')->toString()))
            ->latest('sold_at')
            ->paginate($this->perPage($request, 25));

        return SaleResource::collection($sales);
    }

    public function store(Request $request, SaleService $saleService, WorkSessionService $workSessionService): SaleResource
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'reward_redeemed_total' => ['nullable', 'numeric', 'min:0'],
            'discount_concept' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_breakdown' => ['nullable', 'array'],
            'payment_breakdown.cash' => ['nullable', 'numeric', 'min:0'],
            'payment_breakdown.card' => ['nullable', 'numeric', 'min:0'],
            'payment_breakdown.transfer' => ['nullable', 'numeric', 'min:0'],
            'payment_breakdown.reward_balance' => ['nullable', 'numeric', 'min:0'],
            'payment_breakdown.debt' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.beverage_id' => ['nullable', 'integer', 'exists:beverages,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.customization_option_ids' => ['nullable', 'array'],
            'items.*.customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'items.*.special_customization_name' => ['nullable', 'string', 'max:255'],
            'items.*.special_customization_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.special_instructions' => ['nullable', 'string'],
        ]);

        if (
            in_array($validated['payment_method'], [PaymentMethod::RewardBalance->value, PaymentMethod::Debt->value], true)
            && empty($validated['customer_id'])
        ) {
            throw ValidationException::withMessages([
                'customer_id' => ['Debes seleccionar un cliente para este método de pago.'],
            ]);
        }

        if (
            $validated['payment_method'] === PaymentMethod::Mixed->value
            && empty(array_filter($validated['payment_breakdown'] ?? [], fn ($amount) => (float) $amount > 0))
        ) {
            throw ValidationException::withMessages([
                'payment_breakdown' => ['Captura al menos un componente del pago mixto.'],
            ]);
        }

        if (
            isset($validated['payment_breakdown']['reward_balance'])
            && round((float) $validated['payment_breakdown']['reward_balance'], 2) !== round((float) ($validated['reward_redeemed_total'] ?? 0), 2)
        ) {
            throw ValidationException::withMessages([
                'payment_breakdown.reward_balance' => ['El saldo usado debe coincidir con el componente de saldo del pago.'],
            ]);
        }

        $itemErrors = $this->validateSaleItems($validated['items']);

        if ($itemErrors !== []) {
            throw ValidationException::withMessages($itemErrors);
        }

        $user = User::query()->findOrFail($validated['user_id']);
        $workSession = $workSessionService->currentFor($user);

        abort_if($workSession === null, 422, 'El colaborador no tiene sucursal confirmada hoy.');

        try {
            $sale = $saleService->register($validated, $user, $workSession);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'payment_method' => [$exception->getMessage()],
            ]);
        }

        return new SaleResource($sale->load([
            'branch',
            'user',
            'customer.qrCodes',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
            'mercadoPagoPointOrder',
        ]));
    }

    public function show(Sale $sale): SaleResource
    {
        abort_if(
            request()->user()?->hasLimitedAccountingView()
            && in_array($sale->payment_method, [PaymentMethod::Cash, PaymentMethod::Mixed], true),
            403,
        );

        return new SaleResource($sale->load([
            'branch',
            'user',
            'customer.qrCodes',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
            'mercadoPagoPointOrder',
        ]));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, array<int, string>>
     */
    protected function validateSaleItems(array $items): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $hasBeverage = ! empty($item['beverage_id']);
            $hasSize = ! empty($item['size_id']);
            $hasProduct = ! empty($item['product_id']);
            $hasTemporaryName = filled(trim((string) ($item['item_name'] ?? '')));
            $hasTemporaryPrice = array_key_exists('unit_price', $item) && $item['unit_price'] !== null;

            if ($hasBeverage xor $hasSize) {
                $errors["items.$index.size_id"] = ['Selecciona bebida y tamaño juntos.'];
            }

            if ($hasProduct && ($hasBeverage || $hasSize)) {
                $errors["items.$index.product_id"] = ['Cada línea debe ser bebida, producto o temporal, pero no mezclar tipos.'];
            }

            if ($hasProduct && ($hasTemporaryName || $hasTemporaryPrice)) {
                $errors["items.$index.item_name"] = ['No combines un producto de catálogo con un concepto temporal en la misma línea.'];
            }

            if (! $hasBeverage && ! $hasProduct && ! ($hasTemporaryName && $hasTemporaryPrice)) {
                $errors["items.$index.item_name"] = ['Cada línea debe tener una bebida, un producto o un concepto temporal con precio.'];
            }
        }

        return $errors;
    }
}

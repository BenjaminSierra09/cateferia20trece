<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Enums\TableOrderStatus;
use App\Http\Resources\SaleResource;
use App\Http\Resources\TableOrderResource;
use App\Models\TableOrder;
use App\Models\User;
use App\Services\TableOrderService;
use App\Services\WorkSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TableOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = TableOrder::query()
            ->with($this->relations())
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
                fn ($query) => $query->where('status', TableOrderStatus::Open->value),
            )
            ->latest('opened_at')
            ->paginate($this->perPage($request, 25));

        return TableOrderResource::collection($orders);
    }

    public function store(Request $request, TableOrderService $tableOrderService, WorkSessionService $workSessionService): TableOrderResource
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'table_ids' => ['nullable', 'array'],
            'table_ids.*' => ['integer', 'exists:dining_tables,id'],
            'table_name' => ['nullable', 'string', 'max:80'],
            'label' => ['nullable', 'string', 'max:120'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        $workSession = $workSessionService->currentFor($user);

        abort_if($workSession === null, 422, 'El colaborador no tiene sucursal confirmada hoy.');

        try {
            $order = $tableOrderService->open($validated, $user, $workSession);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['table_name' => [$exception->getMessage()]]);
        }

        return new TableOrderResource($order);
    }

    public function show(TableOrder $tableOrder): TableOrderResource
    {
        return new TableOrderResource($tableOrder->load($this->relations()));
    }

    public function addItems(Request $request, TableOrder $tableOrder, TableOrderService $tableOrderService): TableOrderResource
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.beverage_id' => ['nullable', 'integer', 'exists:beverages,id'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.customization_option_ids' => ['nullable', 'array'],
            'items.*.customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
            'items.*.guest_name' => ['nullable', 'string', 'max:80'],
            'items.*.special_instructions' => ['nullable', 'string'],
        ]);

        $itemErrors = $this->validateSaleItems($validated['items']);

        if ($itemErrors !== []) {
            throw ValidationException::withMessages($itemErrors);
        }

        try {
            $order = $tableOrderService->addItems($tableOrder, $validated['items']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['items' => [$exception->getMessage()]]);
        }

        return new TableOrderResource($order);
    }

    public function merge(Request $request, TableOrder $tableOrder, TableOrderService $tableOrderService): TableOrderResource
    {
        $validated = $request->validate([
            'source_order_ids' => ['required', 'array', 'min:1'],
            'source_order_ids.*' => ['integer', 'exists:table_orders,id'],
        ]);

        try {
            $order = $tableOrderService->merge($tableOrder, $validated['source_order_ids']);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['source_order_ids' => [$exception->getMessage()]]);
        }

        return new TableOrderResource($order);
    }

    public function close(Request $request, TableOrder $tableOrder, TableOrderService $tableOrderService): \Illuminate\Http\Resources\Json\JsonResource
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'payment_method' => ['required_without:splits', Rule::enum(PaymentMethod::class)],
            'discount_total' => ['nullable', 'numeric', 'min:0'],
            'reward_redeemed_total' => ['nullable', 'numeric', 'min:0'],
            'discount_concept' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'payment_breakdown' => ['nullable', 'array'],
            'splits' => ['nullable', 'array', 'min:1'],
            'splits.*.customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'splits.*.payment_method' => ['required_with:splits', Rule::enum(PaymentMethod::class)],
            'splits.*.payment_breakdown' => ['nullable', 'array'],
            'splits.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'splits.*.reward_redeemed_total' => ['nullable', 'numeric', 'min:0'],
            'splits.*.discount_concept' => ['nullable', 'string', 'max:255'],
            'splits.*.notes' => ['nullable', 'string'],
            'splits.*.items' => ['required_with:splits', 'array', 'min:1'],
            'splits.*.items.*.item_id' => ['required', 'integer', 'exists:table_order_items,id'],
            'splits.*.items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        try {
            $result = $tableOrderService->close($tableOrder, $validated, $user);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['table_order' => [$exception->getMessage()]]);
        }

        return new class($result) extends \Illuminate\Http\Resources\Json\JsonResource
        {
            public function toArray(Request $request): array
            {
                return [
                    'table_order' => new TableOrderResource($this->resource['order']),
                    'sales' => SaleResource::collection(collect($this->resource['sales'])->map->load([
                        'branch',
                        'user',
                        'customer.qrCodes',
                        'items.size',
                        'items.beverage',
                        'items.product',
                        'items.customizations',
                    ])),
                ];
            }
        };
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

    /**
     * @return array<int, string>
     */
    protected function relations(): array
    {
        return [
            'branch',
            'user',
            'customer.qrCodes',
            'tables',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
            'sales',
        ];
    }
}

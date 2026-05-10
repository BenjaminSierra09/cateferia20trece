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

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sales = Sale::query()
            ->with([
                'branch',
                'user.branch',
                'customer.qrCodes',
                'items.size',
                'items.beverage',
                'items.product',
                'items.customizations',
            ])
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.beverage_id' => ['nullable', 'integer', 'exists:beverages,id'],
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

        $user = User::query()->findOrFail($validated['user_id']);
        $workSession = $workSessionService->currentFor($user);

        abort_if($workSession === null, 422, 'El colaborador no tiene sucursal confirmada hoy.');

        $sale = $saleService->register($validated, $user, $workSession);

        return new SaleResource($sale->load([
            'branch',
            'user.branch',
            'customer.qrCodes',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
        ]));
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load([
            'branch',
            'user.branch',
            'customer.qrCodes',
            'items.size',
            'items.beverage',
            'items.product',
            'items.customizations',
        ]));
    }
}

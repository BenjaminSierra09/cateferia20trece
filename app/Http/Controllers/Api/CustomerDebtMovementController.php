<?php

namespace App\Http\Controllers\Api;

use App\Enums\CustomerDebtMovementType;
use App\Http\Resources\CustomerDebtMovementResource;
use App\Models\Customer;
use App\Services\CustomerDebtService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CustomerDebtMovementController extends Controller
{
    public function index(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $movements = $customer->debtMovements()
            ->with(['user', 'branch'])
            ->paginate($this->perPage($request));

        return CustomerDebtMovementResource::collection($movements);
    }

    public function store(
        Request $request,
        Customer $customer,
        CustomerDebtService $customerDebtService,
    ): CustomerDebtMovementResource {
        $validated = $request->validate([
            'type' => ['required', new Enum(CustomerDebtMovementType::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        try {
            $movement = $customerDebtService->register(
                customer: $customer,
                type: CustomerDebtMovementType::from($validated['type']),
                amount: (float) $validated['amount'],
                notes: $validated['notes'] ?? null,
                user: $request->user(),
                branchId: $validated['branch_id'] ?? null,
                recordedAt: $validated['recorded_at'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'amount' => [$exception->getMessage()],
            ]);
        }

        return new CustomerDebtMovementResource($movement);
    }
}

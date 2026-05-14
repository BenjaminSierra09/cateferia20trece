<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RewardTransactionResource;
use App\Models\Customer;
use App\Models\RewardTransaction;
use App\Services\RewardProgramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RewardTransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $transactions = RewardTransaction::query()
            ->with(['customer', 'sale'])
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->latest('transacted_at')
            ->paginate($this->perPage($request));

        return RewardTransactionResource::collection($transactions);
    }

    public function show(RewardTransaction $rewardTransaction): RewardTransactionResource
    {
        return new RewardTransactionResource($rewardTransaction->load(['customer', 'sale']));
    }

    public function store(
        Request $request,
        Customer $customer,
        RewardProgramService $rewardProgramService,
    ): RewardTransactionResource {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        try {
            $transaction = $rewardProgramService->creditManualBalance(
                customer: $customer,
                amount: (float) $validated['amount'],
                description: $validated['notes'] ?? null,
                transactedAt: isset($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : null,
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'amount' => [$exception->getMessage()],
            ]);
        }

        return new RewardTransactionResource($transaction->load(['customer', 'sale']));
    }
}

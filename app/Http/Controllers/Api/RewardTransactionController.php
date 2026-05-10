<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RewardTransactionResource;
use App\Models\RewardTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}

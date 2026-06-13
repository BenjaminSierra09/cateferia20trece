<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CashRegisterCutResource;
use App\Models\CashRegisterCut;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CashRegisterCutController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->canViewCashSensitiveData(), 403);

        $cuts = CashRegisterCut::query()
            ->with(['branch', 'user', 'workSession'])
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('work_session_id'), fn (Builder $query) => $query->where('work_session_id', $request->integer('work_session_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('cut_at', '>=', $request->date('date_from')?->toDateString()))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('cut_at', '<=', $request->date('date_to')?->toDateString()))
            ->latest('cut_at')
            ->paginate($this->perPage($request, 25));

        return CashRegisterCutResource::collection($cuts);
    }

    public function store(Request $request, CashRegisterService $cashRegisterService): CashRegisterCutResource
    {
        abort_unless($request->user()?->canViewCashSensitiveData(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'work_session_id' => ['nullable', 'integer', 'exists:work_sessions,id'],
            'period_start_at' => ['nullable', 'date'],
            'cut_at' => ['nullable', 'date'],
            'opening_cash_amount' => ['nullable', 'numeric', 'min:0'],
            'counted_cash_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        try {
            $cut = $cashRegisterService->createCut($validated, $user);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'branch_id' => [$exception->getMessage()],
            ]);
        }

        return new CashRegisterCutResource($cut);
    }

    public function show(CashRegisterCut $cashRegisterCut): CashRegisterCutResource
    {
        abort_unless(request()->user()?->canViewCashSensitiveData(), 403);

        return new CashRegisterCutResource($cashRegisterCut->load(['branch', 'user', 'workSession']));
    }
}

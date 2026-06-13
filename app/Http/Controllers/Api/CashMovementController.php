<?php

namespace App\Http\Controllers\Api;

use App\Enums\CashMovementType;
use App\Http\Resources\CashMovementResource;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CashMovementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()?->canViewCashSensitiveData(), 403);

        $movements = CashMovement::query()
            ->with(['branch', 'user', 'workSession'])
            ->when($request->filled('branch_id'), fn (Builder $query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('user_id'), fn (Builder $query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('work_session_id'), fn (Builder $query) => $query->where('work_session_id', $request->integer('work_session_id')))
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('occurred_at', '>=', $request->date('date_from')?->toDateString()))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('occurred_at', '<=', $request->date('date_to')?->toDateString()))
            ->latest('occurred_at')
            ->paginate($this->perPage($request, 25));

        return CashMovementResource::collection($movements);
    }

    public function store(Request $request, CashRegisterService $cashRegisterService): CashMovementResource
    {
        abort_unless($request->user()?->canViewCashSensitiveData(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'work_session_id' => ['nullable', 'integer', 'exists:work_sessions,id'],
            'type' => ['required', Rule::enum(CashMovementType::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'concept' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);

        try {
            $movement = $cashRegisterService->recordMovement($validated, $user);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'branch_id' => [$exception->getMessage()],
            ]);
        }

        return new CashMovementResource($movement);
    }

    public function show(CashMovement $cashMovement): CashMovementResource
    {
        abort_unless(request()->user()?->canViewCashSensitiveData(), 403);

        return new CashMovementResource($cashMovement->load(['branch', 'user', 'workSession']));
    }
}

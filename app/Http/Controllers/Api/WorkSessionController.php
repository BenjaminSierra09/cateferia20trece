<?php

namespace App\Http\Controllers\Api;

use App\Enums\WorkSessionStatus;
use App\Http\Resources\WorkSessionResource;
use App\Models\WorkSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class WorkSessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sessions = WorkSession::query()
            ->with(['user.branch', 'branch'])
            ->withCount('sales')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('work_date')
            ->paginate($this->perPage($request));

        return WorkSessionResource::collection($sessions);
    }

    public function store(Request $request): WorkSessionResource
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'work_date' => ['required', 'date'],
            'clock_in_at' => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date', 'after_or_equal:clock_in_at'],
            'status' => ['required', Rule::enum(WorkSessionStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $session = WorkSession::query()->create($validated);

        return new WorkSessionResource($session->load(['user.branch', 'branch'])->loadCount('sales'));
    }

    public function show(WorkSession $workSession): WorkSessionResource
    {
        return new WorkSessionResource($workSession->load(['user.branch', 'branch'])->loadCount('sales'));
    }

    public function update(Request $request, WorkSession $workSession): WorkSessionResource
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'work_date' => ['sometimes', 'date'],
            'clock_in_at' => ['nullable', 'date'],
            'clock_out_at' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(WorkSessionStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);

        $workSession->update($validated);

        return new WorkSessionResource($workSession->fresh()->load(['user.branch', 'branch'])->loadCount('sales'));
    }

    public function destroy(WorkSession $workSession): Response
    {
        $workSession->delete();

        return response()->noContent();
    }
}

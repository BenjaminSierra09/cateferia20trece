<?php

namespace App\Http\Controllers\Api;

use App\Enums\TableOrderStatus;
use App\Http\Resources\DiningTableResource;
use App\Models\DiningTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiningTableController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'include_inactive' => ['nullable', 'boolean'],
        ]);

        $tables = DiningTable::query()
            ->with(['tableOrders' => fn ($query) => $query->where('status', TableOrderStatus::Open->value)])
            ->where('branch_id', $validated['branch_id'])
            ->when(! $request->boolean('include_inactive'), fn (Builder $query) => $query->where('is_active', true))
            ->orderByRaw('CAST(name AS UNSIGNED), name')
            ->get();

        return DiningTableResource::collection($tables);
    }

    public function store(Request $request): DiningTableResource
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('dining_tables', 'name')->where('branch_id', $request->integer('branch_id')),
            ],
            'seats' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $table = DiningTable::query()->create([
            'branch_id' => $validated['branch_id'],
            'name' => trim($validated['name']),
            'seats' => $validated['seats'] ?? null,
            'is_active' => true,
        ]);

        return new DiningTableResource($table->load([
            'tableOrders' => fn ($query) => $query->where('status', TableOrderStatus::Open->value),
        ]));
    }

    public function destroy(DiningTable $diningTable): Response
    {
        $hasOpenOrder = $diningTable->tableOrders()
            ->where('status', TableOrderStatus::Open->value)
            ->exists();

        if ($hasOpenOrder) {
            throw ValidationException::withMessages([
                'table' => ['No puedes eliminar una mesa con cuenta abierta.'],
            ]);
        }

        $diningTable->delete();

        return response()->noContent();
    }
}

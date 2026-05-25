<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BranchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $branches = Branch::query()
            ->withCount(['workSessions', 'sales'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return BranchResource::collection($branches);
    }

    public function store(Request $request): BranchResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'operating_hours' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch = Branch::query()->create($validated);

        return new BranchResource($branch->loadCount(['workSessions', 'sales']));
    }

    public function show(Branch $branch): BranchResource
    {
        return new BranchResource($branch->loadCount(['workSessions', 'sales']));
    }

    public function update(Request $request, Branch $branch): BranchResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'operating_hours' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $branch->update($validated);

        return new BranchResource($branch->fresh()->loadCount(['workSessions', 'sales']));
    }

    public function destroy(Branch $branch): Response
    {
        $branch->delete();

        return response()->noContent();
    }
}

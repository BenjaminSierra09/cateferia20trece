<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->with('qrCodes')
            ->withCount('sales')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return CustomerResource::collection($customers);
    }

    public function store(Request $request): CustomerResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer = Customer::create($validated);

        return new CustomerResource($customer->load('qrCodes', 'rewardTransactions')->loadCount('sales'));
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->load('qrCodes', 'rewardTransactions')->loadCount('sales'));
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer->update($validated);

        return new CustomerResource($customer->fresh()->load('qrCodes', 'rewardTransactions')->loadCount('sales'));
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }
}

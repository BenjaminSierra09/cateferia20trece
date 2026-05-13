<?php

namespace App\Http\Controllers\Api;

use App\Enums\RewardTier;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerQrCode;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->with(['qrCodes', 'debtMovements'])
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
            'qr_codes' => ['sometimes', 'array'],
            'qr_codes.*.uuid' => ['required', 'uuid', 'distinct'],
            'qr_codes.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $customer = Customer::create([
            'reward_balance' => 0,
            'reward_year' => (int) now()->format('Y'),
            'annual_drink_count' => 0,
            'reward_tier' => RewardTier::Bronze,
            ...collect($validated)->except('qr_codes')->all(),
        ]);
        $this->syncQrCodes($customer, collect($validated['qr_codes'] ?? []));

        return new CustomerResource($customer->load('qrCodes', 'rewardTransactions', 'debtMovements')->loadCount('sales'));
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer->load('qrCodes', 'rewardTransactions', 'debtMovements')->loadCount('sales'));
    }

    public function update(Request $request, Customer $customer): CustomerResource
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'birthday' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'qr_codes' => ['sometimes', 'array'],
            'qr_codes.*.uuid' => ['required', 'uuid', 'distinct'],
            'qr_codes.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $customer->update(collect($validated)->except('qr_codes')->all());

        if (array_key_exists('qr_codes', $validated)) {
            $this->syncQrCodes($customer, collect($validated['qr_codes']));
        }

        return new CustomerResource($customer->fresh()->load('qrCodes', 'rewardTransactions', 'debtMovements')->loadCount('sales'));
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }

    /**
     * @param  Collection<int, array{uuid:string,is_active?:bool}>  $qrCodes
     */
    protected function syncQrCodes(Customer $customer, Collection $qrCodes): void
    {
        $normalizedQrCodes = $qrCodes
            ->map(fn (array $qrCode): array => [
                'uuid' => Str::lower(trim($qrCode['uuid'])),
                'is_active' => (bool) ($qrCode['is_active'] ?? true),
            ])
            ->unique('uuid')
            ->values();

        $submittedUuids = $normalizedQrCodes->pluck('uuid')->all();

        $customer->qrCodes()
            ->whereNotIn('uuid', $submittedUuids)
            ->update([
                'customer_id' => null,
                'is_active' => false,
            ]);

        $normalizedQrCodes->each(function (array $qrCode) use ($customer): void {
            CustomerQrCode::query()->updateOrCreate(
                ['uuid' => $qrCode['uuid']],
                [
                    'customer_id' => $customer->id,
                    'is_active' => $qrCode['is_active'],
                ],
            );
        });
    }
}

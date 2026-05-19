<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Services\EvolutionWhatsAppService;
use App\Support\TonalpohualliCalendar;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Title('Clientes')]
class Manager extends Component
{
    use WithPagination;

    #[Url(as: 'search', keep: true)]
    public string $search = '';

    #[Url(as: 'per_page', keep: true)]
    public int $perPage = 10;

    #[Url(as: 'view', keep: true)]
    public string $viewMode = 'list';

    /**
     * @var array<int>
     */
    public array $selectedCustomerIds = [];

    public bool $selectPage = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectedCustomerIds(): void
    {
        $this->syncSelectionState();
    }

    public function togglePageSelection(): void
    {
        $visibleIds = $this->visibleCustomerIds();

        if ($this->selectPage) {
            $this->selectedCustomerIds = array_values(array_diff($this->selectedCustomerIds, $visibleIds));
            $this->selectPage = false;

            return;
        }

        $this->selectedCustomerIds = array_values(array_unique([...$this->selectedCustomerIds, ...$visibleIds]));
        $this->selectPage = true;
    }

    public function clearSelection(): void
    {
        $this->selectedCustomerIds = [];
        $this->selectPage = false;
    }

    public function deactivateSelected(): void
    {
        if ($this->selectedCustomerIds === []) {
            return;
        }

        Customer::query()->whereKey($this->selectedCustomerIds)->update(['is_active' => false]);

        $this->clearSelection();

        Flux::toast(text: 'Clientes desactivados.');
    }

    public function reactivateSelected(): void
    {
        if ($this->selectedCustomerIds === []) {
            return;
        }

        Customer::query()->whereKey($this->selectedCustomerIds)->update(['is_active' => true]);

        $this->clearSelection();

        Flux::toast(text: 'Clientes reactivados.');
    }

    public function toggleActive(int $customerId): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        $customer->update(['is_active' => ! $customer->is_active]);

        Flux::toast(text: $customer->is_active ? 'Cliente reactivado.' : 'Cliente desactivado.');
    }

    public function sendWelcomeMessage(int $customerId): void
    {
        $customer = Customer::query()
            ->with('qrCodes')
            ->findOrFail($customerId);

        if (blank($customer->phone)) {
            Flux::toast(variant: 'danger', text: 'Este cliente no tiene teléfono registrado.');

            return;
        }

        $qrCode = $customer->qrCodes->firstWhere('is_active', true) ?? $customer->qrCodes->first();

        if ($qrCode === null) {
            Flux::toast(variant: 'danger', text: 'Este cliente todavía no tiene un QR vinculado.');

            return;
        }

        try {
            app(EvolutionWhatsAppService::class)->sendCustomerCredential($customer, $qrCode);
        } catch (Throwable $throwable) {
            report($throwable);

            Flux::toast(variant: 'danger', text: 'No fue posible enviar el mensaje de bienvenida.');

            return;
        }

        Flux::toast(variant: 'success', text: 'Mensaje de bienvenida enviado.');
    }

    protected function customerQuery(): Builder
    {
        return Customer::query()
            ->with(['qrCodes', 'debtMovements'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($customerQuery) {
                    $customerQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->latest();
    }

    /**
     * @return array<int>
     */
    protected function visibleCustomerIds(): array
    {
        return $this->customerQuery()
            ->paginate($this->perPage)
            ->pluck('id')
            ->all();
    }

    protected function syncSelectionState(): void
    {
        $visibleIds = $this->visibleCustomerIds();

        $this->selectPage = $visibleIds !== []
            && count(array_intersect($visibleIds, $this->selectedCustomerIds)) === count($visibleIds);
    }

    /**
     * Render the customer page.
     */
    public function render(): View
    {
        $customers = $this->customerQuery()->paginate($this->perPage);

        $calendar = app(TonalpohualliCalendar::class);

        return view('livewire.customers.manager', [
            'customers' => $customers,
            'tonalliByCustomerId' => $customers->getCollection()->mapWithKeys(fn (Customer $customer): array => [
                $customer->id => $customer->tonalpohualli(),
            ]),
        ])->layout('layouts.app');
    }
}

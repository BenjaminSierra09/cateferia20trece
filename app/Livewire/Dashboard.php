<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Models\Sale;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * Render the dashboard page.
     */
    public function render(): View
    {
        return view('livewire.dashboard', [
            'overview' => app(ReportService::class)->overview(),
            'recentSales' => Sale::query()->with(['branch', 'customer'])->latest('sold_at')->limit(5)->get(),
            'recentCustomers' => Customer::query()->latest()->limit(5)->get(),
        ])->layout('layouts.app');
    }
}

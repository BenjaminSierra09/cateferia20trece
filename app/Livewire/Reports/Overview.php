<?php

namespace App\Livewire\Reports;

use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Services\ReportExcelExportService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Reportes')]
class Overview extends Component
{
    public ?int $branch_id = null;

    public string $payment_method = '';

    public string $date_from = '';

    public string $date_to = '';

    #[Url(as: 'view', keep: true)]
    public string $presentationMode = 'visual';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $branchChart = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $paymentChart = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $salesTimelineChart = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $topBeveragesChart = [];

    public function exportExcel(ReportExcelExportService $reportExcelExportService): StreamedResponse
    {
        $contents = $reportExcelExportService->overview($this->reportFilters());

        return response()->streamDownload(
            fn () => print $contents,
            'reporte-ventas-'.now()->format('Y-m-d-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    /**
     * Render the reports page.
     */
    public function render(): View
    {
        $overview = app(ReportService::class)->overview($this->reportFilters());

        $this->branchChart = collect($overview['sales_by_branch'])->map(fn (array $item): array => [
            'branch' => $item['branch'],
            'branch_corta' => $this->compactChartLabel($item['branch']),
            'total' => $item['total'],
        ])->all();

        $this->paymentChart = collect($overview['sales_by_payment_method'])->map(fn (array $item): array => [
            'metodo' => $item['payment_method'],
            'metodo_corto' => $this->compactChartLabel($item['payment_method']),
            'total' => $item['total'],
        ])->all();

        $this->salesTimelineChart = $overview['sales_timeline'];

        $this->topBeveragesChart = collect($overview['top_beverages'])->map(fn (array $item): array => [
            'bebida' => $item['item_name'],
            'bebida_corta' => $this->compactChartLabel($item['item_name']),
            'ingresos' => $item['revenue'],
        ])->all();

        return view('livewire.reports.overview', [
            'overview' => $overview,
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->get(),
            'paymentMethods' => PaymentMethod::cases(),
        ])->layout('layouts.app');
    }

    public function activeDateRangeLabel(): string
    {
        if ($this->date_from !== '' && $this->date_to !== '') {
            return "{$this->date_from} a {$this->date_to}";
        }

        if ($this->date_from !== '') {
            return "Desde {$this->date_from}";
        }

        if ($this->date_to !== '') {
            return "Hasta {$this->date_to}";
        }

        return 'Periodo actual';
    }

    protected function compactChartLabel(string $label): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($label)) ?? $label;

        return Str::limit($normalized, 18, '…');
    }

    /**
     * @return array<string, mixed>
     */
    private function reportFilters(): array
    {
        return [
            'branch_id' => $this->branch_id,
            'payment_method' => $this->payment_method !== '' ? $this->payment_method : null,
            'date_from' => $this->date_from !== '' ? $this->date_from : null,
            'date_to' => $this->date_to !== '' ? $this->date_to : null,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\CustomerQrCode;
use App\Services\CustomerFavoriteBeverageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class PublicPagesController extends Controller
{
    /**
     * Show the public home page.
     */
    public function home(): View
    {
        $galleryImages = collect(File::files(public_path('gallery')))
            ->filter(fn ($file): bool => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp']))
            ->sortBy(fn ($file): string => $file->getFilename())
            ->map(fn ($file): string => asset('gallery/'.$file->getFilename()))
            ->values()
            ->all();

        return view('welcome', [
            'galleryImages' => $galleryImages,
        ]);
    }

    /**
     * Show the public terms page.
     */
    public function terms(): View
    {
        return view('public.terms');
    }

    /**
     * Show the public privacy notice.
     */
    public function privacy(): View
    {
        return view('public.privacy');
    }

    /**
     * Show the public rewards explainer.
     */
    public function rewards(): View
    {
        return view('public.rewards', [
            'rewardTiers' => $this->rewardTiers(),
        ]);
    }

    /**
     * Show the QR lookup landing page.
     */
    public function lookup(): View
    {
        return view('public.lookup');
    }

    /**
     * Show a public customer portal resolved from a QR UUID.
     */
    public function customerPortal(string $uuid, CustomerFavoriteBeverageService $favoriteBeverageService): View
    {
        $qrCode = CustomerQrCode::query()
            ->with('customer')
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

        $qrCode->update([
            'last_scanned_at' => now(),
        ]);

        $customer = $qrCode->customer->load([
            'qrCodes',
            'rewardTransactions' => fn ($query) => $query->latest('transacted_at')->limit(12),
            'debtMovements' => fn ($query) => $query->latest('recorded_at')->limit(12),
        ])->loadCount('sales');

        $recentSales = $customer->sales()
            ->with([
                'branch',
                'items.customizations',
            ])
            ->where('status', SaleStatus::Completed->value)
            ->latest('sold_at')
            ->limit(12)
            ->get();

        $favoriteBeverages = $favoriteBeverageService->topForCustomer($customer);

        return view('public.customer-portal', [
            'customer' => $customer,
            'favoriteBeverages' => $favoriteBeverages,
            'recentSales' => $recentSales,
            'rewardTiers' => $this->rewardTiers(),
        ]);
    }

    /**
     * Describe the reward program for public pages.
     *
     * @return array<int, array{name:string, visits:string, bonus:string, description:string}>
     */
    protected function rewardTiers(): array
    {
        return [
            [
                'name' => 'Cobre',
                'visits' => 'Desde el registro',
                'bonus' => '5% de bonificación',
                'description' => 'Toda persona registrada comienza aquí y puede acumular saldo a favor con sus compras calificadas.',
            ],
            [
                'name' => 'Plata',
                'visits' => '30 visitas',
                'bonus' => '10% de bonificación',
                'description' => 'Al completar 30 días distintos de visita en el año, la bonificación sube automáticamente.',
            ],
            [
                'name' => 'Oro',
                'visits' => '45 visitas',
                'bonus' => '15% de bonificación',
                'description' => 'El nivel más alto del programa para clientes con mayor frecuencia anual.',
            ],
        ];
    }
}

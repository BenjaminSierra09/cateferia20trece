<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\CustomerQrCode;
use App\Services\CustomerCardRenderer;
use App\Services\CustomerFavoriteBeverageService;
use Illuminate\Contracts\View\View;

class PublicPagesController extends Controller
{
    /**
     * Show the public home page.
     */
    public function home(): View
    {
        return view('welcome', [
            'galleryImages' => [
                'san-francisco-storefront.webp' => 'Entrada de Café 20Trece en San Francisco 3, con una bicicleta frente a la fachada',
                'san-francisco-hallway.webp' => 'Pasillo de entrada al café, con cuadros y plantas',
                'coffe-shop-22.jpg' => 'Cappuccino con un corazón dibujado en la espuma',
                'san-francisco-street.webp' => 'Fachada del edificio sobre la calle San Francisco',
                'coffe-shop-17.jpg' => 'Café servido junto a una bolsa de Café 20Trece',
                'coffe-shop-4.jpg' => 'Vitrina con pan dulce',
                'coffe-shop-26.jpg' => 'Bebida fría en un vaso de Café 20Trece',
                'san-francisco-interior.webp' => 'Salón interior con puertas arqueadas y piso de mosaico',
                'san-francisco-courtyard.webp' => 'Escalones y arcos junto al patio del café',
                'san-francisco-counter.webp' => 'Barra y menú de Café 20Trece bajo un techo de vidrio',
            ],
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
    public function customerPortal(
        string $uuid,
        CustomerFavoriteBeverageService $favoriteBeverageService,
        CustomerCardRenderer $customerCardRenderer,
    ): View {
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
        $primaryQrCode = $customer->qrCodes->firstWhere('is_active', true) ?? $customer->qrCodes->first();

        return view('public.customer-portal', [
            'customer' => $customer,
            'customerCardImageUrl' => $primaryQrCode ? $customerCardRenderer->svgDataUrl($customer, $primaryQrCode, asset('logotipo.png')) : null,
            'favoriteBeverages' => $favoriteBeverages,
            'primaryQrCode' => $primaryQrCode,
            'portalUrl' => $primaryQrCode ? route('public.qr.show', ['uuid' => $primaryQrCode->uuid]) : null,
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicInvoiceRequest;
use App\Mail\InvoiceRequestMail;
use App\Models\Sale;
use App\Services\EvolutionWhatsAppService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicInvoiceRequestController extends Controller
{
    /**
     * Show the public invoice (CFDI) request form.
     */
    public function create(Request $request): View
    {
        $sale = filled($request->query('token'))
            ? Sale::query()->where('billing_token', $request->query('token'))->first()
            : null;

        return view('public.invoice', [
            'billingToken' => $request->query('token'),
            'paymentMethod' => $sale?->paymentMethodSummary(),
            'paymentForms' => PublicInvoiceRequest::PAYMENT_FORMS,
            'suggestedPaymentForm' => $sale?->suggestedInvoicePaymentForm(),
            'regimenes' => PublicInvoiceRequest::REGIMENES,
        ]);
    }

    /**
     * Receive a public invoice request and forward the fiscal data.
     */
    public function store(PublicInvoiceRequest $request, EvolutionWhatsAppService $whatsAppService): RedirectResponse
    {
        $data = $request->safe()->except('website');
        $sale = Sale::query()
            ->with(['branch', 'items.customizations'])
            ->where('billing_token', $data['billing_token'])
            ->firstOrFail();

        $soldAt = $sale->sold_at?->timezone(config('app.timezone'))->format('d/m/Y H:i');
        $saleTotal = '$'.number_format((float) $sale->total, 2);
        $paymentMethod = $sale->paymentMethodSummary();
        $invoicePaymentMethod = $data['invoice_payment_method'].' - '.PublicInvoiceRequest::PAYMENT_FORMS[$data['invoice_payment_method']];

        $whatsAppService->sendInvoiceRequestToAccounting($sale, $data);

        Mail::to(config('services.invoicing.email'))
            ->send(new InvoiceRequestMail(
                billingToken: $data['billing_token'],
                rfc: $data['rfc'],
                razonSocial: $data['razon_social'],
                regimenFiscal: $data['regimen_fiscal'],
                codigoPostal: $data['codigo_postal'],
                email: $data['email'],
                telefono: $data['telefono'],
                saleTotal: $saleTotal,
                soldAt: $soldAt,
                paymentMethod: $paymentMethod,
                invoicePaymentMethod: $invoicePaymentMethod,
            ));

        return redirect()
            ->route('public.invoice')
            ->with('invoice_status', 'Recibimos tu solicitud de factura. Procesaremos tu CFDI y lo enviaremos al correo proporcionado.');
    }
}

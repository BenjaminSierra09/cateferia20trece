<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicInvoiceRequest;
use App\Mail\InvoiceRequestMail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class PublicInvoiceRequestController extends Controller
{
    /**
     * Show the public invoice (CFDI) request form.
     */
    public function create(): View
    {
        return view('public.invoice', [
            'regimenes' => PublicInvoiceRequest::REGIMENES,
        ]);
    }

    /**
     * Receive a public invoice request and forward the fiscal data by email.
     */
    public function store(PublicInvoiceRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('website');

        Mail::to(config('services.invoicing.email'))
            ->send(new InvoiceRequestMail(
                rfc: $data['rfc'],
                razonSocial: $data['razon_social'],
                regimenFiscal: $data['regimen_fiscal'],
                codigoPostal: $data['codigo_postal'],
                email: $data['email'],
                telefono: $data['telefono'],
                numeroVenta: $data['numero_venta'],
            ));

        return redirect()
            ->route('public.invoice')
            ->with('invoice_status', 'Recibimos tu solicitud de factura. Procesaremos tu CFDI y lo enviaremos al correo proporcionado.');
    }
}

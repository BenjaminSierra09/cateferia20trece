<x-mail::message>
# Nueva solicitud de factura

Un cliente solicitó su factura (CFDI) a través del sitio de {{ config('app.name') }}.

<x-mail::panel>
**Código de facturación:** {{ $billingToken }}
@if ($soldAt)
<br>**Fecha de venta:** {{ $soldAt }}
@endif
@if ($saleTotal)
<br>**Total:** {{ $saleTotal }}
@endif
@if ($paymentMethod)
<br>**Método registrado en venta:** {{ $paymentMethod }}
@endif
@if ($invoicePaymentMethod)
<br>**Método de pago para CFDI:** {{ $invoicePaymentMethod }}
@endif
</x-mail::panel>

<x-mail::table>
| Dato fiscal | Detalle |
| :--- | :--- |
| RFC | {{ $rfc }} |
| Razón social | {{ $razonSocial }} |
| Régimen fiscal | {{ $regimenFiscal }} |
| Código postal | {{ $codigoPostal }} |
| Correo para envío | {{ $email }} |
| Teléfono | {{ $telefono }} |
</x-mail::table>

Emite el CFDI con estos datos y envíalo al correo indicado. Puedes responder directamente a este mensaje para contactar al cliente.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::message>
<div align="center" style="margin-bottom: 24px;">
	<img src="{{ asset('logotipo.png') }}" alt="{{ config('app.name') }}" width="72" style="display: inline-block; border-radius: 16px;">
</div>

# Gracias por tu compra, {{ $sale->customer?->name ?? 'cliente' }}

Hemos registrado tu venta #{{ $sale->id }}. Aquí tienes el resumen con los datos más importantes.

<x-mail::table>
| Concepto | Detalle |
| :--- | :--- |
| Sucursal | {{ $sale->branch?->name ?? 'N/A' }} |
| Fecha y hora | {{ $sale->sold_at?->format('d/m/Y H:i') ?? 'N/A' }} |
| Colaborador | {{ $sale->user?->name ?? 'N/A' }} |
| Método de pago | {{ $sale->payment_method->label() }} |
</x-mail::table>

@if ($sale->customer)
## Información del cliente

<x-mail::table>
| Concepto | Detalle |
| :--- | :--- |
| Nivel | {{ $sale->customer->reward_tier->label() }} |
| Saldo disponible | ${{ number_format((float) $sale->customer->reward_balance, 2, '.', ',') }} |
| Saldo de deuda | ${{ number_format((float) $sale->customer->debtBalance(), 2, '.', ',') }} |
@if ($sale->customer->email)
| Correo | {{ $sale->customer->email }} |
@endif
</x-mail::table>
@endif

## Artículos

<x-mail::table>
| Producto | Cantidad | Total |
| :--- | ---: | ---: |
@foreach ($sale->items as $item)
| {{ $item->item_name }} | {{ $item->quantity }} | ${{ number_format((float) $item->line_total, 2, '.', ',') }} |
@endforeach
</x-mail::table>

<x-mail::panel>
<div style="display: grid; gap: 6px;">
	<div><strong>Subtotal:</strong> ${{ number_format((float) $sale->subtotal, 2, '.', ',') }}</div>
	<div><strong>Descuento:</strong> ${{ number_format((float) $sale->discount_total, 2, '.', ',') }}</div>
	<div><strong>Saldo usado:</strong> ${{ number_format((float) $sale->reward_redeemed_total, 2, '.', ',') }}</div>
	<div><strong>Total pagado:</strong> ${{ number_format((float) $sale->total, 2, '.', ',') }}</div>
</div>
</x-mail::panel>

Si necesitas una aclaración sobre esta compra, responde este correo y con gusto te ayudamos.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>

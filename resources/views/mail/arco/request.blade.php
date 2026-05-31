<x-mail::message>
# Nueva solicitud de derechos ARCO

Se recibió una solicitud para ejercer derechos ARCO a través del aviso de privacidad de {{ config('app.name') }}.

<x-mail::panel>
**Derechos solicitados:** {{ implode(', ', $derechos) }}
</x-mail::panel>

<x-mail::table>
| Dato | Detalle |
| :--- | :--- |
| Nombre | {{ $nombre }} |
| Correo de contacto | {{ $email }} |
| Teléfono | {{ $telefono ?: 'No proporcionado' }} |
| Identificación de la cuenta | {{ $cuentaIdentificador ?: 'No proporcionada' }} |
</x-mail::table>

**Detalle de la solicitud:**

{{ $detalle }}

Atiende esta solicitud dentro de los plazos de la LFPDPPP (responder en máximo 20 días hábiles y, de proceder, hacerla efectiva dentro de los 15 días siguientes). Verifica la identidad del titular antes de ejecutar cualquier acción. Puedes responder directamente a este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>

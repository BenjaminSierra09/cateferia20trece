<?php

namespace App\Ai\Tools;

use App\Models\Branch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CafeInfoTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Responde preguntas frecuentes con datos OFICIALES de Café 20Trece: horarios y ubicación de sucursales, programa de recompensas, aviso de privacidad, derechos ARCO, términos y condiciones, facturación y contacto. Úsala siempre en lugar de inventar estos datos o enlaces.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $topic = (string) ($request->all()['topic'] ?? 'todo');

        $sections = $this->sections();

        $map = [
            'horarios' => ['sucursales'],
            'ubicacion' => ['sucursales'],
            'contacto' => ['sucursales', 'facturacion', 'privacidad'],
            'recompensas' => ['recompensas'],
            'privacidad' => ['privacidad'],
            'arco' => ['arco'],
            'terminos' => ['terminos'],
            'facturacion' => ['facturacion'],
        ];

        $keys = $map[$topic] ?? array_keys($sections);

        return (string) json_encode(
            array_intersect_key($sections, array_flip($keys)),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Build every information section from real routes, config, and branch data.
     *
     * @return array<string, mixed>
     */
    private function sections(): array
    {
        return [
            'sucursales' => $this->branches(),
            'recompensas' => [
                'descripcion' => 'Programa de lealtad de Café 20Trece: acumulas saldo de recompensas con tus compras y puedes consultarlo aquí mismo por WhatsApp.',
                'mas_informacion' => route('public.rewards'),
            ],
            'privacidad' => [
                'aviso_de_privacidad' => route('public.privacy'),
                'correo' => config('services.privacy.email'),
            ],
            'arco' => [
                'descripcion' => 'Derechos ARCO: Acceso, Rectificación, Cancelación y Oposición sobre tus datos personales.',
                'como_ejercerlos' => 'Envía tu solicitud ARCO desde el formulario del aviso de privacidad o escribe al correo de privacidad.',
                'aviso_de_privacidad' => route('public.privacy'),
                'correo' => config('services.privacy.email'),
            ],
            'terminos' => [
                'terminos_y_condiciones' => route('public.terms'),
            ],
            'facturacion' => [
                'solicitar_factura' => route('public.invoice'),
                'correo' => config('services.invoicing.email'),
            ],
        ];
    }

    /**
     * Resolve active branches with their address, phone, and opening hours.
     *
     * @return array<int, array<string, mixed>>
     */
    private function branches(): array
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'address', 'city', 'phone', 'operating_hours'])
            ->map(fn (Branch $branch): array => [
                'nombre' => $branch->name,
                'direccion' => trim(implode(', ', array_filter([$branch->address, $branch->city]))) ?: null,
                'telefono' => $branch->phone,
                'horario' => $branch->operating_hours,
            ])
            ->all();
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()
                ->enum(['horarios', 'ubicacion', 'recompensas', 'privacidad', 'arco', 'terminos', 'facturacion', 'contacto', 'todo'])
                ->description('Tema sobre el que pregunta el cliente. Usa "todo" para información general.')
                ->required(),
        ];
    }
}

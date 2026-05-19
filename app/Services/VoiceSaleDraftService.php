<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Beverage;
use App\Models\BranchCustomizationPriceOverride;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class VoiceSaleDraftService
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    /**
     * Build a sale draft from a spoken audio note.
     *
     * @return array<string, mixed>
     */
    public function fromAudio(
        UploadedFile $audio,
        User $user,
        WorkSession $workSession,
        string $language = 'es',
        ?string $notes = null,
        ?int $customerId = null,
        ?string $customerUuid = null,
    ): array {
        $this->ensureOpenAiIsConfigured();

        $submittedAt = now()->toIso8601String();
        $transcript = $this->transcribeAudio($audio, $language);
        $catalog = $this->catalogSnapshotFor($workSession);
        $draft = $this->interpretTranscript(
            transcript: $transcript,
            user: $user,
            workSession: $workSession,
            submittedAt: $submittedAt,
            catalog: $catalog,
            notes: $notes,
            customerUuid: $customerUuid,
        );

        return [
            'transcript' => $transcript,
            'submitted_at' => $submittedAt,
            'collaborator' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'branch' => [
                'id' => $workSession->branch_id,
                'name' => $workSession->branch?->name,
            ],
            'sale_payload' => [
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'payment_method' => $draft['payment_method'] ?: PaymentMethod::Cash->value,
                'discount_total' => (float) ($draft['discount_total'] ?? 0),
                'reward_redeemed_total' => (float) ($draft['reward_redeemed_total'] ?? 0),
                'discount_concept' => $draft['discount_concept'] ?: null,
                'notes' => $draft['notes'] ?: null,
                'payment_breakdown' => $this->normalizePaymentBreakdown($draft['payment_breakdown'] ?? []),
                'items' => $this->normalizeItems($draft['items'] ?? []),
            ],
            'assumptions' => Arr::wrap($draft['assumptions'] ?? []),
        ];
    }

    protected function ensureOpenAiIsConfigured(): void
    {
        if (blank(config('ai.providers.openai.key'))) {
            throw new RuntimeException('OPENAI_API_KEY no está configurado.');
        }
    }

    protected function openAiClient()
    {
        return $this->http
            ->baseUrl(rtrim((string) config('ai.providers.openai.url'), '/'))
            ->withToken((string) config('ai.providers.openai.key'))
            ->timeout(60)
            ->acceptJson();
    }

    protected function transcribeAudio(UploadedFile $audio, string $language): string
    {
        $stream = fopen($audio->getRealPath(), 'r');

        if ($stream === false) {
            throw new RuntimeException('No se pudo leer el audio recibido.');
        }

        try {
            $response = $this->openAiClient()
                ->attach(
                    'file',
                    $stream,
                    $audio->getClientOriginalName(),
                    ['Content-Type' => $audio->getMimeType() ?: 'application/octet-stream'],
                )
                ->asMultipart()
                ->post('/audio/transcriptions', [
                    'model' => config('ai.voice_sale.transcription_model'),
                    'language' => $language,
                    'response_format' => 'json',
                    'prompt' => 'Transcribe pedidos de cafeteria en espanol de Mexico. Conserva cantidades, tamanos, precios, metodos de pago y nombres tal como se entiendan.',
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible transcribir el audio con OpenAI.', previous: $exception);
        } finally {
            fclose($stream);
        }

        $transcript = trim((string) $response->json('text'));

        if ($transcript === '') {
            throw new RuntimeException('OpenAI no devolvió una transcripción utilizable.');
        }

        return $transcript;
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return array<string, mixed>
     */
    protected function interpretTranscript(
        string $transcript,
        User $user,
        WorkSession $workSession,
        string $submittedAt,
        array $catalog,
        ?string $notes,
        ?string $customerUuid,
    ): array {
        $payload = [
            'model' => config('ai.voice_sale.response_model'),
            'input' => [
                [
                    'role' => 'developer',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->developerInstructions(),
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => json_encode([
                                'collaborator' => [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                ],
                                'branch' => [
                                    'id' => $workSession->branch_id,
                                    'name' => $workSession->branch?->name,
                                ],
                                'submitted_at' => $submittedAt,
                                'customer_uuid' => $customerUuid,
                                'audio_notes' => $notes,
                                'transcript' => $transcript,
                                'catalog' => $catalog,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                        ],
                    ],
                ],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'voice_sale_draft',
                    'strict' => true,
                    'schema' => $this->draftSchema(),
                ],
            ],
        ];

        try {
            $response = $this->openAiClient()
                ->post('/responses', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('No fue posible interpretar el pedido con OpenAI Responses.', previous: $exception);
        }

        $outputText = trim((string) $response->json('output_text'));

        if ($outputText === '') {
            throw new RuntimeException('OpenAI Responses no devolvió un JSON interpretable.');
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($outputText, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI Responses devolvió un formato inesperado.');
        }

        return $decoded;
    }

    protected function developerInstructions(): string
    {
        return <<<'TEXT'
Convierte la transcripcion de una venta hablada de cafetería a JSON.

Reglas:
- El pago por default es "cash" si la transcripcion no menciona metodo de pago.
- Usa exclusivamente IDs del catalogo proporcionado cuando una bebida, tamano, opcion o producto correspondan claramente.
- Corrige errores foneticos evidentes si el catalogo los aclara, por ejemplo "capuchio" -> "capuchino".
- Si un producto no corresponde con claridad a un producto de catalogo, tratalo como temporal.
- Si la transcripcion incluye un precio para un producto no catalogado, devuelve item_type "temporary", item_name y unit_price.
- Para bebidas usa item_type "beverage" y llena beverage_id, size_id y customization_option_ids.
- Para productos de catalogo usa item_type "product" y llena product_id.
- No inventes clientes. customer_id siempre queda fuera de este JSON.
- Si no sabes algo, deja el campo nulo y agrega una nota corta en assumptions.
- Devuelve solo JSON valido segun el schema.
TEXT;
    }

    /**
     * @return array<string, mixed>
     */
    protected function draftSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'payment_method',
                'payment_breakdown',
                'reward_redeemed_total',
                'discount_total',
                'discount_concept',
                'notes',
                'assumptions',
                'items',
            ],
            'properties' => [
                'payment_method' => [
                    'type' => 'string',
                    'enum' => array_map(fn (PaymentMethod $method): string => $method->value, PaymentMethod::cases()),
                ],
                'payment_breakdown' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['cash', 'card', 'transfer', 'reward_balance', 'debt'],
                    'properties' => [
                        'cash' => ['type' => ['number', 'null']],
                        'card' => ['type' => ['number', 'null']],
                        'transfer' => ['type' => ['number', 'null']],
                        'reward_balance' => ['type' => ['number', 'null']],
                        'debt' => ['type' => ['number', 'null']],
                    ],
                ],
                'reward_redeemed_total' => ['type' => 'number'],
                'discount_total' => ['type' => 'number'],
                'discount_concept' => ['type' => ['string', 'null']],
                'notes' => ['type' => ['string', 'null']],
                'assumptions' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'item_type',
                            'beverage_id',
                            'product_id',
                            'size_id',
                            'item_name',
                            'unit_price',
                            'quantity',
                            'customization_option_ids',
                            'special_instructions',
                        ],
                        'properties' => [
                            'item_type' => [
                                'type' => 'string',
                                'enum' => ['beverage', 'product', 'temporary'],
                            ],
                            'beverage_id' => ['type' => ['integer', 'null']],
                            'product_id' => ['type' => ['integer', 'null']],
                            'size_id' => ['type' => ['integer', 'null']],
                            'item_name' => ['type' => ['string', 'null']],
                            'unit_price' => ['type' => ['number', 'null']],
                            'quantity' => ['type' => 'integer'],
                            'customization_option_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                            ],
                            'special_instructions' => ['type' => ['string', 'null']],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function catalogSnapshotFor(WorkSession $workSession): array
    {
        $beverages = Beverage::query()
            ->with(['category', 'sizePrices.size', 'customizationOptions.type'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Beverage $beverage) use ($workSession): array {
                return [
                    'id' => $beverage->id,
                    'name' => $beverage->name,
                    'category' => $beverage->category?->name,
                    'is_hot' => $beverage->is_hot,
                    'sizes' => $beverage->sizePrices
                        ->filter(fn ($sizePrice) => (bool) $sizePrice->size?->is_active)
                        ->map(function ($sizePrice) use ($beverage, $workSession): array {
                            $price = DB::table('branch_beverage_price_overrides')
                                ->where('branch_id', $workSession->branch_id)
                                ->where('beverage_id', $beverage->id)
                                ->where('size_id', $sizePrice->size_id)
                                ->value('price');

                            return [
                                'size_id' => $sizePrice->size_id,
                                'name' => $sizePrice->size?->name,
                                'capacity_label' => $sizePrice->size?->capacity_label,
                                'price' => round((float) ($price ?? $sizePrice->price), 2),
                            ];
                        })
                        ->values()
                        ->all(),
                    'customizations' => $beverage->customizationOptions
                        ->filter(fn ($option) => (bool) $option->is_available)
                        ->map(function ($option) use ($workSession): array {
                            $price = BranchCustomizationPriceOverride::query()
                                ->where('branch_id', $workSession->branch_id)
                                ->where('customization_option_id', $option->id)
                                ->value('price');

                            return [
                                'id' => $option->id,
                                'name' => $option->name,
                                'type' => $option->type?->name,
                                'price' => round((float) ($price ?? $option->price), 2),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_type' => $product->unit_type,
                'base_price' => round((float) $product->base_price, 2),
            ])
            ->values()
            ->all();

        return [
            'beverages' => $beverages,
            'products' => $products,
        ];
    }

    /**
     * @param  array<string, mixed>  $paymentBreakdown
     * @return array<string, float>|null
     */
    protected function normalizePaymentBreakdown(array $paymentBreakdown): ?array
    {
        $normalized = collect($paymentBreakdown)
            ->filter(fn (mixed $amount): bool => $amount !== null && (float) $amount > 0)
            ->mapWithKeys(fn (mixed $amount, string $method): array => [$method => round((float) $amount, 2)])
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item): array {
                $normalized = [
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                    'special_instructions' => filled($item['special_instructions'] ?? null)
                        ? Str::limit(trim((string) $item['special_instructions']), 255, '')
                        : null,
                ];

                return match ($item['item_type'] ?? null) {
                    'beverage' => $normalized + [
                        'beverage_id' => $item['beverage_id'],
                        'size_id' => $item['size_id'],
                        'customization_option_ids' => array_values(array_map('intval', Arr::wrap($item['customization_option_ids'] ?? []))),
                    ],
                    'product' => $normalized + [
                        'product_id' => $item['product_id'],
                    ],
                    default => $normalized + [
                        'item_name' => trim((string) ($item['item_name'] ?? 'Producto temporal')),
                        'unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                    ],
                };
            })
            ->values()
            ->all();
    }
}

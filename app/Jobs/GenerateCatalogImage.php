<?php

namespace App\Jobs;

use App\Support\CatalogImageManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Image;
use Throwable;

#[Tries(3)]
#[Backoff([15, 60, 300])]
#[Timeout(180)]
class GenerateCatalogImage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $modelClass,
        public int|string $modelId,
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->modelClass.':'.$this->modelId;
    }

    /**
     * Execute the job.
     */
    public function handle(CatalogImageManager $catalogImageManager): void
    {
        if (! class_exists($this->modelClass)) {
            return;
        }

        $model = $this->modelClass::query()->find($this->modelId);

        if ($model === null || ! $catalogImageManager->shouldGenerateImage($model)) {
            return;
        }

        $providers = $this->availableProviders();

        if ($providers === []) {
            Log::warning('No AI image provider is configured for catalog image generation.', [
                'model' => $this->modelClass,
                'id' => $this->modelId,
            ]);

            return;
        }

        try {
            $generatedImage = Image::of($catalogImageManager->promptFor($model))
                ->square()
                ->quality('medium')
                ->timeout(180)
                ->generate(provider: count($providers) === 1 ? $providers[0] : $providers);
        } catch (Throwable $exception) {
            Log::warning('Catalog AI image generation failed.', [
                'model' => $this->modelClass,
                'id' => $this->modelId,
                'providers' => array_map(
                    static fn (Lab $provider): string => $provider->value,
                    $providers,
                ),
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $path = $catalogImageManager->storeGeneratedImage($model, (string) $generatedImage);

        $model->forceFill(['image_path' => $path])->saveQuietly();
    }

    /**
     * Resolve the available image providers in failover order.
     *
     * @return array<int, Lab>
     */
    protected function availableProviders(): array
    {
        return array_values(array_filter([
            filled(config('ai.providers.openai.key')) ? Lab::OpenAI : null,
            filled(config('ai.providers.gemini.key')) ? Lab::Gemini : null,
            filled(config('ai.providers.xai.key')) ? Lab::xAI : null,
        ]));
    }
}

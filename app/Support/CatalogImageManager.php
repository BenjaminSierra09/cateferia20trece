<?php

namespace App\Support;

use App\Jobs\GenerateCatalogImage;
use App\Models\Beverage;
use App\Models\BeverageCategory;
use App\Models\CustomizationOption;
use App\Models\CustomizationType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CatalogImageManager
{
    /**
     * Queue AI image generation for a catalog model when it is missing an image.
     */
    public function queueImageGeneration(Model $model): bool
    {
        if (! $this->shouldGenerateImage($model)) {
            return false;
        }

        GenerateCatalogImage::dispatch($model::class, $model->getKey())
            ->onConnection('database');

        return true;
    }

    /**
     * Store an uploaded catalog image as a centered square.
     */
    public function storeSquareUpload(UploadedFile $uploadedFile, string $directory, string $disk = 'public'): string
    {
        $extension = $this->normalizedExtension(
            $uploadedFile->guessExtension() ?: $uploadedFile->extension() ?: 'png',
        );

        $path = trim($directory, '/').'/'.Str::uuid().'.'.$extension;

        Storage::disk($disk)->put(
            $path,
            $this->makeSquareImage((string) $uploadedFile->getRealPath(), $extension),
            ['visibility' => 'public'],
        );

        return $path;
    }

    /**
     * Determine if the given model should receive an AI generated image.
     */
    public function shouldGenerateImage(Model $model): bool
    {
        return blank($model->getAttribute('image_path'))
            && filled($this->displayName($model));
    }

    /**
     * Build a stable public path for AI generated images.
     */
    public function generatedImagePathFor(Model $model): string
    {
        $name = Str::slug($this->displayName($model) ?: class_basename($model));

        return 'catalog/generated/'.$this->directorySegment($model).'/'.$model->getKey().'-'.$name.'.png';
    }

    /**
     * Store an AI generated image on the public disk.
     */
    public function storeGeneratedImage(Model $model, string $contents, string $disk = 'public'): string
    {
        $path = $this->generatedImagePathFor($model);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'catalog-ai-');

        if ($temporaryPath === false) {
            throw new RuntimeException('No se pudo crear un archivo temporal para la imagen generada.');
        }

        file_put_contents($temporaryPath, $contents);

        try {
            Storage::disk($disk)->put(
                $path,
                $this->makeSquareImage($temporaryPath, 'png'),
                ['visibility' => 'public'],
            );
        } finally {
            @unlink($temporaryPath);
        }

        return $path;
    }

    /**
     * Build an AI prompt for the given catalog model.
     */
    public function promptFor(Model $model): string
    {
        $name = $this->displayName($model);
        $description = trim((string) $model->getAttribute('description'));

        return match ($model::class) {
            Beverage::class => "Fotografia cuadrada realista de menu para cafeteria de \"{$name}\". {$description} Bebida centrada, estudio comercial, apetecible, fondo limpio, iluminacion profesional, sin texto, sin logotipos.",
            Product::class => "Fotografia cuadrada realista de producto de cafeteria llamado \"{$name}\". {$description} Producto centrado, estilo comercial premium, fondo limpio, iluminacion suave, sin texto, sin logotipos.",
            BeverageCategory::class => "Imagen cuadrada editorial que represente la categoria de cafeteria \"{$name}\". {$description} Una composicion apetecible y limpia relacionada con esa categoria, estilo fotografia comercial, sin texto, sin logotipos.",
            CustomizationType::class => "Imagen cuadrada clara y realista para personalizacion de cafeteria llamada \"{$name}\". {$description} Mostrar ingredientes o complementos representativos, composicion limpia, estilo comercial, sin texto, sin logotipos.",
            CustomizationOption::class => "Imagen cuadrada realista de complemento de cafeteria llamado \"{$name}\". Mostrar el ingrediente o extra de forma centrada, fondo limpio, fotografia comercial, sin texto, sin logotipos.",
            default => "Imagen cuadrada realista de producto de cafeteria llamado \"{$name}\". Fondo limpio, iluminacion profesional, sin texto, sin logotipos.",
        };
    }

    /**
     * Build a square image binary from the given local file path.
     */
    public function makeSquareImage(string $sourcePath, string $extension): string
    {
        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el archivo de imagen.');
        }

        $source = imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('No se pudo procesar el archivo de imagen.');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $squareSize = min($width, $height);
            $offsetX = (int) floor(($width - $squareSize) / 2);
            $offsetY = (int) floor(($height - $squareSize) / 2);

            $canvas = imagecreatetruecolor($squareSize, $squareSize);

            if ($canvas === false) {
                throw new RuntimeException('No se pudo crear el lienzo cuadrado.');
            }

            try {
                $this->prepareCanvas($canvas, $extension, $squareSize);

                imagecopyresampled(
                    $canvas,
                    $source,
                    0,
                    0,
                    $offsetX,
                    $offsetY,
                    $squareSize,
                    $squareSize,
                    $squareSize,
                    $squareSize,
                );

                ob_start();

                $written = match ($extension) {
                    'jpg', 'jpeg' => imagejpeg($canvas, null, 90),
                    'webp' => imagewebp($canvas, null, 90),
                    'gif' => imagegif($canvas),
                    default => imagepng($canvas, null, 6),
                };

                $binary = ob_get_clean();

                if ($written === false || $binary === false) {
                    throw new RuntimeException('No se pudo exportar la imagen cuadrada.');
                }

                return $binary;
            } finally {
                imagedestroy($canvas);
            }
        } finally {
            imagedestroy($source);
        }
    }

    /**
     * Prepare the target GD canvas according to the final image format.
     */
    protected function prepareCanvas(\GdImage $canvas, string $extension, int $squareSize): void
    {
        if (in_array($extension, ['png', 'webp', 'gif'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);

            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);

            imagefilledrectangle($canvas, 0, 0, $squareSize, $squareSize, $transparent);

            if ($extension === 'gif') {
                imagecolortransparent($canvas, $transparent);
            }

            return;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $squareSize, $squareSize, $white);
    }

    /**
     * Normalize the desired image extension.
     */
    protected function normalizedExtension(string $extension): string
    {
        return match (Str::lower($extension)) {
            'jpeg', 'jpg' => 'jpg',
            'png' => 'png',
            'gif' => 'gif',
            'webp' => 'webp',
            default => 'png',
        };
    }

    /**
     * Resolve a readable name for prompts and filenames.
     */
    protected function displayName(Model $model): string
    {
        return trim((string) $model->getAttribute('name'));
    }

    /**
     * Resolve a directory segment per catalog model type.
     */
    protected function directorySegment(Model $model): string
    {
        return match ($model::class) {
            Beverage::class => 'beverages',
            Product::class => 'products',
            BeverageCategory::class => 'categories',
            CustomizationType::class => 'customization-types',
            CustomizationOption::class => 'customization-options',
            default => Str::kebab(class_basename($model)),
        };
    }
}

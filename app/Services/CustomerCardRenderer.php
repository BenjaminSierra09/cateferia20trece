<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use RuntimeException;

class CustomerCardRenderer
{
    /**
     * Render a downloadable customer card as an SVG data URL.
     */
    public function svgDataUrl(Customer $customer, CustomerQrCode $qrCode, string $logoUrl): string
    {
        $cardSvg = $this->renderCardSvg($customer, $qrCode, $logoUrl);

        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($cardSvg);
    }

    /**
     * Render the full customer card SVG.
     */
    public function renderCardSvg(Customer $customer, CustomerQrCode $qrCode, string $logoUrl): string
    {
        $escapedLogoUrl = e($this->inlineImageDataUrl($logoUrl));
        $escapedCustomerName = e($customer->name);
        $escapedCustomerUuid = e($qrCode->uuid);

        return trim(<<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="900" height="1460" viewBox="0 0 900 1460" role="img" aria-label="Tarjeta de cliente de {$escapedCustomerName}">
                <rect width="900" height="1460" fill="#F7F1E8" />
                <rect x="48" y="36" width="804" height="1388" rx="42" fill="#F4E0CB" />
                <image href="{$escapedLogoUrl}" x="286" y="92" width="328" height="328" preserveAspectRatio="xMidYMid meet" />
                <text x="450" y="492" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="42" font-weight="700" fill="#8B5E34">Tarjeta de cliente</text>
                <rect x="206" y="610" width="488" height="488" fill="#FFFFFF" />
                {$this->renderQrSvg($qrCode->uuid)}
                <text x="450" y="1170" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="600" fill="#C7A16E">Escanea este QR en caja</text>
                <text x="450" y="1288" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="58" font-weight="700" fill="#2A2118">{$escapedCustomerName}</text>
                <text x="450" y="1392" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="500" fill="#6B5B4A">{$escapedCustomerUuid}</text>
            </svg>
        SVG);
    }

    /**
     * Render the full customer card as PNG bytes.
     */
    public function renderCardPng(Customer $customer, CustomerQrCode $qrCode, string $logoUrl): string
    {
        try {
            $image = new Imagick;
            $image->newImage(900, 1460, new ImagickPixel('#F7F1E8'));
            $image->setImageFormat('png');

            $background = new ImagickDraw;
            $background->setFillColor(new ImagickPixel('#F4E0CB'));
            $background->setStrokeColor(new ImagickPixel('#F4E0CB'));
            $background->roundRectangle(48, 36, 852, 1424, 42, 42);
            $image->drawImage($background);

            $logoPath = $this->resolveLocalImagePath($logoUrl);

            if ($logoPath !== null) {
                $logo = new Imagick($logoPath);
                $logo->resizeImage(328, 328, Imagick::FILTER_LANCZOS, 1, true);
                $image->compositeImage($logo, Imagick::COMPOSITE_DEFAULT, 286, 92);
                $logo->clear();
                $logo->destroy();
            }

            $qr = new Imagick;
            $qr->readImageBlob($this->renderQrPng($qrCode->uuid));
            $image->compositeImage($qr, Imagick::COMPOSITE_DEFAULT, 266, 670);
            $qr->clear();
            $qr->destroy();

            $this->annotateCenteredText($image, 'Tarjeta de cliente', 492, 42, '#8B5E34', 700);
            $this->annotateCenteredText($image, 'Escanea este QR en caja', 1170, 30, '#C7A16E', 600);
            $this->annotateCenteredText($image, $customer->name, 1288, 58, '#2A2118', 700);
            $this->annotateCenteredText($image, $qrCode->uuid, 1392, 24, '#6B5B4A', 500);

            $png = $image->getImageBlob();
            $image->clear();
            $image->destroy();

            return $png;
        } catch (ImagickException $exception) {
            throw new RuntimeException('No fue posible generar la credencial PNG del cliente.', previous: $exception);
        }
    }

    /**
     * Render the full customer card as raw base64 PNG.
     */
    public function pngBase64(Customer $customer, CustomerQrCode $qrCode, string $logoUrl): string
    {
        return base64_encode($this->renderCardPng($customer, $qrCode, $logoUrl));
    }

    /**
     * Render the QR as an embeddable SVG fragment.
     */
    public function renderQrSvg(string $value): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(
                size: 368,
                margin: 1,
                fill: Fill::uniformColor(new Rgb(58, 44, 37), new Rgb(255, 255, 255)),
            ),
            new SvgImageBackEnd,
        );

        $svg = (new Writer($renderer))->writeString($value);
        $svg = preg_replace('/<\?xml.*?\?>\s*/', '', $svg) ?? $svg;
        $svg = preg_replace('/<svg\b/', '<svg x="266" y="670"', $svg, 1) ?? $svg;

        return $svg;
    }

    /**
     * Render the QR as a PNG image blob.
     */
    public function renderQrPng(string $value): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(
                size: 368,
                margin: 1,
                fill: Fill::uniformColor(new Rgb(58, 44, 37), new Rgb(255, 255, 255)),
            ),
            new ImagickImageBackEnd('png', 100, false),
        );

        return (new Writer($renderer))->writeString($value);
    }

    /**
     * Convert a local public asset URL into an inline data URL when possible.
     */
    private function inlineImageDataUrl(string $imageUrl): string
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $imageUrl;
        }

        $absolutePath = public_path(ltrim($path, '/'));

        if (! is_file($absolutePath)) {
            return $imageUrl;
        }

        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            return $imageUrl;
        }

        $mimeType = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }

    private function resolveLocalImagePath(string $imageUrl): ?string
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $absolutePath = public_path(ltrim($path, '/'));

        return is_file($absolutePath) ? $absolutePath : null;
    }

    private function annotateCenteredText(
        Imagick $image,
        string $text,
        int $baselineY,
        int $fontSize,
        string $color,
        int $fontWeight,
    ): void {
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel($color));
        $draw->setFont($this->resolveFontPath($fontWeight >= 600));
        $draw->setFontSize($fontSize);
        $draw->setFontWeight($fontWeight);
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        $image->annotateImage($draw, 450, $baselineY, 0, $text);
    }

    private function resolveFontPath(bool $bold): string
    {
        $candidates = $bold
            ? [
                '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
                '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation2/LiberationSans-Bold.ttf',
            ]
            : [
                '/System/Library/Fonts/Supplemental/Arial.ttf',
                '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
            ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('No encontramos una fuente disponible para renderizar la credencial del cliente.');
    }
}

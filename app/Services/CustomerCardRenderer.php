<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

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
}

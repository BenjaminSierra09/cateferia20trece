<?php

namespace App\Contracts;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\Sale;

interface WhatsAppService
{
    public function isConfigured(): bool;

    public function sendMessage(string $number, string $text): ?string;

    public function sendReaction(string $number, string $messageId, string $emoji): ?string;

    public function sendImage(
        string $number,
        string $contents,
        string $fileName,
        string $mimeType,
        ?string $caption = null,
    ): ?string;

    /**
     * @param  array<int, string>  $bodyParameters
     */
    public function sendMarketingTemplate(
        string $number,
        string $templateName,
        string $language,
        array $bodyParameters = [],
    ): ?string;

    public function sendCustomerCredential(Customer $customer, CustomerQrCode $qrCode): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendInvoiceRequestToAccounting(Sale $sale, array $data): void;
}

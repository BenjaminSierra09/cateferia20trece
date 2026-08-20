<?php

namespace App\Contracts;

use App\Models\Customer;
use App\Models\CustomerQrCode;
use App\Models\Sale;

interface WhatsAppService
{
    public function isConfigured(): bool;

    public function sendMessage(string $number, string $text): ?string;

    public function sendCustomerCredential(Customer $customer, CustomerQrCode $qrCode): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function sendInvoiceRequestToAccounting(Sale $sale, array $data): void;
}

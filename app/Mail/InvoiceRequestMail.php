<?php

namespace App\Mail;

use App\Http\Requests\PublicInvoiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $billingToken,
        public string $rfc,
        public string $razonSocial,
        public string $regimenFiscal,
        public string $codigoPostal,
        public string $email,
        public string $telefono,
        public ?string $saleTotal = null,
        public ?string $soldAt = null,
        public ?string $paymentMethod = null,
        public ?string $invoicePaymentMethod = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de factura - '.$this->billingToken,
            replyTo: [new Address($this->email, $this->razonSocial)],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $regimenLabel = PublicInvoiceRequest::REGIMENES[$this->regimenFiscal] ?? $this->regimenFiscal;

        return new Content(
            markdown: 'mail.invoice.request',
            with: [
                'rfc' => $this->rfc,
                'razonSocial' => $this->razonSocial,
                'regimenFiscal' => $this->regimenFiscal.' - '.$regimenLabel,
                'codigoPostal' => $this->codigoPostal,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'billingToken' => $this->billingToken,
                'saleTotal' => $this->saleTotal,
                'soldAt' => $this->soldAt,
                'paymentMethod' => $this->paymentMethod,
                'invoicePaymentMethod' => $this->invoicePaymentMethod,
            ],
        );
    }
}

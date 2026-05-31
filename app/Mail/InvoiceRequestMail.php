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
        public string $rfc,
        public string $razonSocial,
        public string $regimenFiscal,
        public string $codigoPostal,
        public string $email,
        public string $telefono,
        public string $numeroVenta,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de factura - venta '.$this->numeroVenta,
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
                'numeroVenta' => $this->numeroVenta,
            ],
        );
    }
}

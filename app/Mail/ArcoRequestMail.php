<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArcoRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $derechos
     */
    public function __construct(
        public string $nombre,
        public string $email,
        public ?string $telefono,
        public array $derechos,
        public ?string $cuentaIdentificador,
        public string $detalle,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de derechos ARCO - '.$this->nombre,
            replyTo: [new Address($this->email, $this->nombre)],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $labels = [
            'acceso' => 'Acceso',
            'rectificacion' => 'Rectificación',
            'cancelacion' => 'Cancelación (olvido)',
            'oposicion' => 'Oposición',
            'revocacion' => 'Revocación del consentimiento',
        ];

        return new Content(
            markdown: 'mail.arco.request',
            with: [
                'nombre' => $this->nombre,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'derechos' => array_map(fn (string $right): string => $labels[$right] ?? $right, $this->derechos),
                'cuentaIdentificador' => $this->cuentaIdentificador,
                'detalle' => $this->detalle,
            ],
        );
    }
}

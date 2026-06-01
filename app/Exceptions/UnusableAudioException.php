<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown when an audio note cannot be transcribed because of the audio itself
 * (empty, silent, too noisy, corrupted or an unsupported container) rather than
 * a system failure. Callers translate it into a 422 so it is never reported.
 */
class UnusableAudioException extends RuntimeException
{
    public function __construct(
        string $message = 'No pudimos transcribir el audio. Puede estar vacío, con mucho ruido o dañado; intenta grabarlo de nuevo.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}

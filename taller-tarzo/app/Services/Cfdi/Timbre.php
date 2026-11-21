<?php

namespace App\Services\Cfdi;

/**
 * Resultado de timbrar con el PAC.
 */
final class Timbre
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $pacId,
        public readonly string $xml,
        public readonly ?string $pdf = null,
    ) {}
}

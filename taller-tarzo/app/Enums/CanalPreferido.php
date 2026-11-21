<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CanalPreferido: string implements HasLabel
{
    case WhatsApp = 'whatsapp';
    case Correo = 'correo';
    case Llamada = 'llamada';

    public function getLabel(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Correo => 'Correo electrónico',
            self::Llamada => 'Llamada telefónica',
        };
    }
}

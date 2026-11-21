<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MotivoPausa: string implements HasLabel
{
    case EsperaRefaccion = 'espera_refaccion';
    case Comida = 'comida';
    case OtraUrgencia = 'otra_urgencia';
    case EsperaAutorizacion = 'espera_autorizacion';
    case FinJornada = 'fin_jornada';
    case CambioTarea = 'cambio_tarea';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::EsperaRefaccion => 'Espera de refacción',
            self::Comida => 'Comida',
            self::OtraUrgencia => 'Atender otra urgencia',
            self::EsperaAutorizacion => 'Espera de autorización',
            self::FinJornada => 'Fin de jornada',
            self::CambioTarea => 'Cambió a otra tarea',
            self::Otro => 'Otro',
        };
    }
}

<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Eventos que generan un mensaje al cliente.
 */
enum EventoMensaje: string implements HasLabel
{
    case Ingreso = 'ingreso';
    case PresupuestoListo = 'presupuesto_listo';
    case CambioEtapa = 'cambio_etapa';
    case ListoEntrega = 'listo_entrega';
    case Entregado = 'entregado';
    case RecordatorioServicio = 'recordatorio_servicio';
    case RecordatorioVerificacion = 'recordatorio_verificacion';
    case EncuestaNps = 'encuesta_nps';
    case Respuesta = 'respuesta';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ingreso => 'Vehículo recibido',
            self::PresupuestoListo => 'Presupuesto listo',
            self::CambioEtapa => 'Cambio de etapa',
            self::ListoEntrega => 'Listo para entrega',
            self::Entregado => 'Entregado',
            self::RecordatorioServicio => 'Recordatorio de servicio',
            self::RecordatorioVerificacion => 'Recordatorio de verificación',
            self::EncuestaNps => 'Encuesta de satisfacción',
            self::Respuesta => 'Respuesta del asesor',
        };
    }

    /**
     * Los avisos de la orden siempre se envían; recordatorios y encuestas requieren el consentimiento
     * del cliente ("finalidades adicionales" del aviso de privacidad).
     */
    public function esAdicional(): bool
    {
        return in_array($this, [self::RecordatorioServicio, self::RecordatorioVerificacion, self::EncuestaNps], true);
    }
}

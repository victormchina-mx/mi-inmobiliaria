<?php

namespace Database\Seeders;

use App\Models\Parametro;
use App\Models\PlantillaMensaje;
use App\Models\ReglaMantenimiento;
use Illuminate\Database\Seeder;

/**
 * Módulo 5: plantillas de mensajes, reglas de mantenimiento y plazos de garantía.
 * Solo crea lo que falta; no pisa lo que el dueño ya editó.
 *
 * Las plantillas de WhatsApp deben aprobarse en Meta con el mismo nombre (`plantilla_meta`)
 * y las variables en el orden de `variables_meta`.
 */
class ComunicacionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plantillas() as [$evento, $asunto, $cuerpo, $meta, $variables]) {
            PlantillaMensaje::firstOrCreate(['evento' => $evento, 'canal' => 'correo'], ['asunto' => $asunto, 'cuerpo' => $cuerpo]);
            PlantillaMensaje::firstOrCreate(['evento' => $evento, 'canal' => 'whatsapp'], [
                'cuerpo' => $cuerpo, 'plantilla_meta' => $meta, 'idioma' => 'es_MX', 'variables_meta' => $variables,
            ]);
        }

        foreach ([
            ['Cambio de aceite y filtro', 10000, 6],
            ['Revisión de frenos', 20000, 12],
            ['Rotación y balanceo', 10000, 6],
            ['Afinación', 40000, 24],
        ] as [$nombre, $km, $meses]) {
            ReglaMantenimiento::firstOrCreate(['nombre' => $nombre], ['cada_km' => $km, 'cada_meses' => $meses]);
        }

        foreach ([
            ['garantia_mecanica_meses', 3, 'Garantía de mano de obra mecánica', 'meses'],
            ['garantia_mecanica_km', 5000, 'Garantía de mano de obra mecánica (lo que ocurra primero)', 'km'],
            ['garantia_pintura_meses', 12, 'Garantía de hojalatería y pintura', 'meses'],
            ['garantia_pintura_km', 0, 'Garantía de pintura por kilometraje (0 = sin límite)', 'km'],
            ['garantia_refacciones_meses', 6, 'Garantía de refacciones instaladas', 'meses'],
            ['garantia_refacciones_km', 10000, 'Garantía de refacciones por kilometraje', 'km'],
            ['km_diarios_estimados', 40, 'Uso diario supuesto cuando no hay historial de kilometraje', 'km/día'],
        ] as [$clave, $valor, $descripcion, $unidad]) {
            Parametro::firstOrCreate(['clave' => $clave], compact('valor', 'descripcion', 'unidad'));
        }
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: string, 4: list<string>}>
     */
    private function plantillas(): array
    {
        return [
            ['ingreso', 'Recibimos tu {vehiculo} · {folio}',
                "Hola {cliente}, recibimos tu {vehiculo} ({placas}) con el folio {folio}. Puedes seguir el avance aquí: {enlace}\n\n{taller} · {telefono_taller}",
                'tarzo_ingreso', ['cliente', 'vehiculo', 'folio', 'enlace']],
            ['presupuesto_listo', 'Tu presupuesto está listo · {folio}',
                "Hola {cliente}, el presupuesto de tu {vehiculo} está listo por {total}. Revísalo y autorízalo aquí: {enlace_aprobacion}\n\n{taller}",
                'tarzo_presupuesto', ['cliente', 'vehiculo', 'total', 'enlace_aprobacion']],
            ['cambio_etapa', 'Tu {vehiculo} avanzó a {etapa}',
                "Hola {cliente}, tu {vehiculo} pasó a la etapa «{etapa}». Sigue el avance: {enlace}\n\n{taller}",
                'tarzo_avance', ['cliente', 'vehiculo', 'etapa', 'enlace']],
            ['listo_entrega', '¡Tu {vehiculo} está listo!',
                "Hola {cliente}, ¡tu {vehiculo} ({placas}) está listo para entregarse! Te esperamos en {taller}. Detalle: {enlace}\n\nDudas: {telefono_taller}",
                'tarzo_listo', ['cliente', 'vehiculo', 'placas', 'enlace']],
            ['entregado', 'Gracias por tu confianza · {folio}',
                'Gracias {cliente} por confiar en {taller}. Tu {vehiculo} quedó entregado. Cualquier duda con tu garantía, escríbenos: {telefono_taller}',
                'tarzo_entregado', ['cliente', 'vehiculo', 'taller']],
            ['recordatorio_servicio', 'Es momento de: {servicio}',
                "Hola {cliente}, por kilometraje o tiempo a tu {vehiculo} ({placas}) ya le toca: {servicio}. Agenda tu cita al {telefono_taller}.\n\n{taller}",
                'tarzo_recordatorio_servicio', ['cliente', 'vehiculo', 'servicio', 'telefono_taller']],
            ['recordatorio_verificacion', 'Tu {vehiculo} verifica este periodo',
                'Hola {cliente}, recuerda: {servicio} para tu {vehiculo} ({placas}). Si necesitas revisión previa (emisiones, testigos), llámanos al {telefono_taller}.',
                'tarzo_recordatorio_verificacion', ['cliente', 'servicio', 'placas', 'telefono_taller']],
            ['encuesta_nps', '¿Cómo te atendimos?',
                'Hola {cliente}, ¿qué tan probable es que recomiendes {taller} a un amigo? Califícanos del 0 al 10 (1 minuto): {enlace_encuesta}',
                'tarzo_encuesta', ['cliente', 'taller', 'enlace_encuesta']],
        ];
    }
}

<?php

namespace App\Actions\Piso;

use App\Enums\EstadoTarea;
use App\Enums\MotivoPausa;
use App\Models\RegistroTiempo;
use App\Models\Tarea;
use App\Models\Tecnico;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reloj checador por tarea: iniciar, pausar (con motivo) y terminar.
 * Un técnico solo trabaja una tarea a la vez: iniciar otra pausa la anterior.
 */
class RelojTarea
{
    public function iniciar(Tarea $tarea, Tecnico $tecnico): Tarea
    {
        if ($tarea->estado === EstadoTarea::Terminada) {
            throw ValidationException::withMessages(['tarea' => 'La tarea ya está terminada.']);
        }

        if ($tarea->tecnico_id && $tarea->tecnico_id !== $tecnico->id) {
            throw ValidationException::withMessages(['tarea' => 'Esta tarea está asignada a otro técnico.']);
        }

        return DB::transaction(function () use ($tarea, $tecnico) {
            Tarea::where('tecnico_id', $tecnico->id)
                ->where('estado', EstadoTarea::EnProceso->value)
                ->whereKeyNot($tarea->id)
                ->get()
                ->each(fn (Tarea $otra) => $this->pausar($otra, MotivoPausa::CambioTarea));

            if ($tarea->estado !== EstadoTarea::EnProceso) {
                RegistroTiempo::create(['tarea_id' => $tarea->id, 'tecnico_id' => $tecnico->id, 'inicio' => now()]);
            }

            $tarea->forceFill([
                'tecnico_id' => $tecnico->id,
                'estado' => EstadoTarea::EnProceso,
                'iniciada_at' => $tarea->iniciada_at ?? now(),
            ])->save();

            return $tarea;
        });
    }

    public function pausar(Tarea $tarea, MotivoPausa $motivo): Tarea
    {
        if ($tarea->estado !== EstadoTarea::EnProceso) {
            throw ValidationException::withMessages(['tarea' => 'La tarea no está en proceso.']);
        }

        return DB::transaction(function () use ($tarea, $motivo) {
            $this->cerrarRegistro($tarea, $motivo);
            $tarea->forceFill(['estado' => EstadoTarea::Pausada])->save();

            return $tarea;
        });
    }

    public function terminar(Tarea $tarea): Tarea
    {
        if ($tarea->estado === EstadoTarea::Terminada) {
            return $tarea;
        }

        if ($tarea->registros()->doesntExist() && $tarea->estado === EstadoTarea::Pendiente) {
            throw ValidationException::withMessages(['tarea' => 'Inicia la tarea antes de terminarla para registrar el tiempo.']);
        }

        return DB::transaction(function () use ($tarea) {
            $this->cerrarRegistro($tarea, null);
            $tarea->forceFill(['estado' => EstadoTarea::Terminada, 'terminada_at' => now()])->save();

            return $tarea;
        });
    }

    private function cerrarRegistro(Tarea $tarea, ?MotivoPausa $motivo): void
    {
        $registro = $tarea->registros()->whereNull('fin')->latest('inicio')->first();

        if ($registro) {
            $fin = now();
            $registro->update([
                'fin' => $fin,
                'minutos' => max(0, (int) round($registro->inicio->diffInSeconds($fin) / 60)),
                'motivo_pausa' => $motivo,
            ]);
        }

        $tarea->horas_reales = round($tarea->registros()->sum('minutos') / 60, 2);
    }
}

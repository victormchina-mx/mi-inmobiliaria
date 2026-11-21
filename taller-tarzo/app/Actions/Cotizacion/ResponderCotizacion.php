<?php

namespace App\Actions\Cotizacion;

use App\Enums\EstadoCotizacion;
use App\Enums\EstadoPartida;
use App\Enums\Paga;
use App\Filament\Resources\Cotizaciones\CotizacionResource;
use App\Models\Aprobacion;
use App\Models\Cotizacion;
use App\Models\Partida;
use App\Models\User;
use App\Services\Cotizacion\Cotizador;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registra la autorización (total o parcial) de un cliente o ajustador.
 *
 * - El cliente decide las partidas que paga él (o todas si no es seguro).
 * - El ajustador decide las partidas que paga la aseguradora.
 * - Cuando ya no quedan partidas pendientes, la cotización queda aprobada,
 *   aprobada parcialmente o rechazada, y se avisa al asesor.
 */
class ResponderCotizacion
{
    /**
     * @param  array<int|string, string>  $decisiones  partida_id => 'aprobada' | 'rechazada'
     */
    public function __invoke(
        Cotizacion $cotizacion,
        string $rol,
        array $decisiones,
        string $nombre,
        ?string $comentario = null,
        string $canal = 'enlace',
        ?string $ip = null,
        ?string $dispositivo = null,
        ?User $registradaPor = null,
    ): Aprobacion {
        if ($cotizacion->estado !== EstadoCotizacion::Enviada) {
            throw ValidationException::withMessages(['cotizacion' => 'Esta cotización ya no está esperando autorización.']);
        }

        if (! in_array($rol, EnlaceAprobacion::ROLES, true)) {
            throw ValidationException::withMessages(['rol' => 'Rol no válido.']);
        }

        if (mb_strlen(trim($nombre)) < 3) {
            throw ValidationException::withMessages(['nombre' => 'Escribe el nombre completo de quien autoriza.']);
        }

        $pendientes = self::partidasDelRol($cotizacion, $rol)->where('estado', EstadoPartida::Pendiente);

        if ($pendientes->isEmpty()) {
            throw ValidationException::withMessages(['partidas' => 'No hay partidas pendientes para autorizar.']);
        }

        $aprobacion = DB::transaction(function () use ($cotizacion, $rol, $decisiones, $nombre, $comentario, $canal, $ip, $dispositivo, $registradaPor, $pendientes) {
            $aprobacion = Aprobacion::create([
                'cotizacion_id' => $cotizacion->id,
                'rol' => $rol,
                'canal' => $canal,
                'nombre' => trim($nombre),
                'user_id' => $registradaPor?->id,
                'ip' => $ip,
                'dispositivo' => Str::limit((string) $dispositivo, 250, ''),
                'huella' => $cotizacion->huella(),
                'comentario' => $comentario,
            ]);

            $aprobadas = 0;
            foreach ($pendientes as $partida) {
                // Lo que no se marcó explícitamente como rechazado se considera aprobado.
                $estado = ($decisiones[$partida->id] ?? 'aprobada') === 'rechazada' ? EstadoPartida::Rechazada : EstadoPartida::Aprobada;
                $partida->forceFill(['estado' => $estado, 'aprobacion_id' => $aprobacion->id])->saveQuietly();
                $estado === EstadoPartida::Aprobada && $aprobadas++;
            }

            $aprobacion->update(['partidas_aprobadas' => $aprobadas, 'partidas_rechazadas' => $pendientes->count() - $aprobadas]);

            $this->cerrarSiCorresponde($cotizacion->refresh());

            return $aprobacion;
        });

        $this->avisarAsesor($cotizacion->refresh(), $aprobacion);

        return $aprobacion;
    }

    /**
     * @return Collection<int, Partida>
     */
    public static function partidasDelRol(Cotizacion $cotizacion, string $rol): Collection
    {
        $partidas = $cotizacion->partidas()->with(['diagnostico', 'pieza'])->get();

        if (! $cotizacion->esSeguro()) {
            return $rol === 'cliente' ? $partidas : collect();
        }

        $paga = $rol === 'ajustador' ? Paga::Aseguradora : Paga::Cliente;

        return $partidas->filter(fn (Partida $p) => $p->paga === $paga)->values();
    }

    private function cerrarSiCorresponde(Cotizacion $cotizacion): void
    {
        if ($cotizacion->partidasPendientes()->exists()) {
            app(Cotizador::class)->recalcular($cotizacion);

            return;
        }

        $estados = $cotizacion->partidas()->pluck('estado')->map(fn ($e) => $e instanceof EstadoPartida ? $e : EstadoPartida::from($e));
        $aprobadas = $estados->filter(fn ($e) => $e === EstadoPartida::Aprobada)->count();

        $cotizacion->forceFill([
            'estado' => match (true) {
                $aprobadas === 0 => EstadoCotizacion::Rechazada,
                $aprobadas === $estados->count() => EstadoCotizacion::Aprobada,
                default => EstadoCotizacion::AprobadaParcial,
            },
            'respondida_at' => now(),
        ])->save();

        app(Cotizador::class)->recalcular($cotizacion);
    }

    private function avisarAsesor(Cotizacion $cotizacion, Aprobacion $aprobacion): void
    {
        $asesor = $cotizacion->orden->asesor ?? $cotizacion->autor;

        if (! $asesor || $aprobacion->user_id === $asesor->id) {
            return;
        }

        $quien = $aprobacion->rol === 'ajustador' ? 'El ajustador' : 'El cliente';

        Notification::make()
            ->title("{$quien} respondió la cotización {$cotizacion->folio}")
            ->body("{$aprobacion->nombre}: {$aprobacion->partidas_aprobadas} aprobadas, {$aprobacion->partidas_rechazadas} rechazadas."
                .($aprobacion->comentario ? ' Comentario: '.Str::limit($aprobacion->comentario, 120) : ''))
            ->icon('heroicon-o-check-circle')
            ->color($aprobacion->partidas_rechazadas ? 'warning' : 'success')
            ->actions([
                Action::make('ver')->label('Ver cotización')->url(CotizacionResource::getUrl('edit', ['record' => $cotizacion], panel: 'taller')),
            ])
            ->sendToDatabase($asesor);
    }
}

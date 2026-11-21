<?php

namespace App\Models;

use App\Enums\ElementoInventario;
use App\Enums\Testigo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Inventario digital de ingreso del vehículo, firmado por el cliente.
 */
class Checkin extends Model
{
    use LogsActivity;

    protected $table = 'checkins';

    protected $fillable = [
        'orden_servicio_id', 'user_id', 'nivel_combustible', 'kilometraje', 'foto_odometro', 'fotos',
        'testigos', 'inventario', 'pertenencias', 'observaciones', 'video', 'completado_at',
    ];

    protected function casts(): array
    {
        return [
            'fotos' => 'array',
            'testigos' => 'array',
            'inventario' => 'array',
            'nivel_combustible' => 'integer',
            'kilometraje' => 'integer',
            'acepta_aviso_privacidad' => 'boolean',
            'acepta_condiciones' => 'boolean',
            'firmado_at' => 'datetime',
            'pdf_generado_at' => 'datetime',
            'completado_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // El kilometraje capturado en el check-in manda: actualiza la orden y el vehículo.
        static::saved(function (Checkin $checkin): void {
            if (! $checkin->wasChanged('kilometraje') && ! $checkin->wasRecentlyCreated) {
                return;
            }

            $km = $checkin->kilometraje;
            if (! $km) {
                return;
            }

            $orden = $checkin->orden;
            if ($orden->kilometraje_ingreso !== $km) {
                $orden->forceFill(['kilometraje_ingreso' => $km])->saveQuietly();
            }

            if ($km > (int) $orden->vehiculo->kilometraje) {
                $orden->vehiculo->forceFill(['kilometraje' => $km])->saveQuietly();
            }
        });
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function danos(): HasMany
    {
        return $this->hasMany(DanoPrevio::class)->orderBy('orden');
    }

    public function estaFirmado(): bool
    {
        return $this->firmado_at !== null;
    }

    /**
     * @return list<string>
     */
    public function testigosLegibles(): array
    {
        return collect($this->testigos ?? [])->map(fn ($v) => Testigo::tryFrom($v)?->getLabel() ?? $v)->values()->all();
    }

    /**
     * @return list<string>
     */
    public function inventarioLegible(): array
    {
        return collect($this->inventario ?? [])->map(fn ($v) => ElementoInventario::tryFrom($v)?->getLabel() ?? $v)->values()->all();
    }

    /**
     * Elementos de inventario que NO estaban al recibir el vehículo.
     *
     * @return list<string>
     */
    public function inventarioFaltante(): array
    {
        $presentes = $this->inventario ?? [];

        return collect(ElementoInventario::cases())
            ->reject(fn (ElementoInventario $e) => in_array($e->value, $presentes, true))
            ->map(fn (ElementoInventario $e) => $e->getLabel())
            ->values()->all();
    }

    /**
     * Lo que el cliente acepta al firmar. Su huella sha256 se guarda como evidencia:
     * si el check-in cambia después de firmado, la huella ya no coincide.
     *
     * @return array<string, mixed>
     */
    public function contenidoFirmable(): array
    {
        $this->loadMissing('danos', 'orden.vehiculo');

        return [
            'folio' => $this->orden->folio,
            'vehiculo' => $this->orden->vehiculo->only(['vin', 'placas', 'marca', 'modelo', 'anio']),
            'kilometraje' => $this->kilometraje,
            'combustible' => $this->nivel_combustible,
            'testigos' => array_values($this->testigos ?? []),
            'inventario' => array_values($this->inventario ?? []),
            'pertenencias' => $this->pertenencias,
            'danos' => $this->danos->map(fn (DanoPrevio $d) => [$d->zona->value, $d->tipo->value, $d->descripcion, $d->foto])->all(),
            'fotos' => array_values($this->fotos ?? []),
        ];
    }

    public function calcularHuella(): string
    {
        return hash('sha256', json_encode($this->contenidoFirmable(), JSON_UNESCAPED_UNICODE));
    }

    public function huellaVigente(): bool
    {
        return $this->firma_huella !== null && hash_equals($this->firma_huella, $this->calcularHuella());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nivel_combustible', 'kilometraje', 'testigos', 'inventario', 'pertenencias', 'firmado_por', 'firmado_at', 'completado_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

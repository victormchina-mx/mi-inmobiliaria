<?php

namespace App\Models;

use App\Enums\Prioridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Hallazgo del técnico. En el Módulo 2 se convierte en partida de cotización.
 */
class Diagnostico extends Model
{
    use LogsActivity;

    protected $table = 'diagnosticos';

    protected $fillable = ['orden_servicio_id', 'tecnico_id', 'user_id', 'hallazgo', 'detalle', 'codigos_dtc', 'prioridad', 'fotos', 'convertido_a_partida'];

    protected function casts(): array
    {
        return [
            'prioridad' => Prioridad::class,
            'fotos' => 'array',
            'convertido_a_partida' => 'boolean',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Normaliza "p0301 p0420,c1234" → "P0301, P0420, C1234".
     */
    public static function normalizarDtc(?string $codigos): ?string
    {
        if (blank($codigos)) {
            return null;
        }

        preg_match_all('/[PBCU][0-9A-F]{4}/i', $codigos, $m);

        return collect($m[0])->map(fn ($c) => strtoupper($c))->unique()->implode(', ') ?: null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

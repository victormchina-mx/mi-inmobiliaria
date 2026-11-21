<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Proveedor extends Model
{
    use LogsActivity;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'rfc', 'contacto', 'telefono', 'email', 'dias_entrega', 'notas', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'dias_entrega' => 'integer'];
    }

    public function ordenesCompra(): HasMany
    {
        return $this->hasMany(OrdenCompra::class);
    }

    /**
     * Porcentaje de órdenes recibidas a tiempo (para evaluar proveedores).
     */
    public function cumplimiento(): ?int
    {
        $recibidas = $this->ordenesCompra()->whereNotNull('recibida_at')->whereNotNull('fecha_estimada')->get(['recibida_at', 'fecha_estimada']);

        if ($recibidas->isEmpty()) {
            return null;
        }

        $aTiempo = $recibidas->filter(fn ($oc) => $oc->recibida_at->startOfDay()->lte($oc->fecha_estimada))->count();

        return (int) round($aTiempo / $recibidas->count() * 100);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

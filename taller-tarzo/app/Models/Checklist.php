<?php

namespace App\Models;

use App\Support\Checklists;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    public const CALIDAD = 'calidad';

    public const ENTREGA = 'entrega';

    protected $table = 'checklists';

    protected $fillable = ['orden_servicio_id', 'tipo', 'items', 'aprobado', 'observaciones', 'kilometraje', 'firma', 'firmado_por', 'user_id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'aprobado' => 'boolean', 'kilometraje' => 'integer'];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return list<string> etiquetas de los puntos no cumplidos
     */
    public function pendientes(): array
    {
        $marcados = $this->items ?? [];

        return collect(Checklists::puntos($this->tipo))
            ->reject(fn ($etiqueta, $clave) => in_array($clave, $marcados, true))
            ->values()->all();
    }
}

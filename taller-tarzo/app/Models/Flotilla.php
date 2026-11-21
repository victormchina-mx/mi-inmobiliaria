<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Flotilla extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'flotillas';

    protected $fillable = ['nombre', 'dias_credito', 'limite_credito', 'descuento_mano_obra', 'descuento_refacciones', 'notas', 'activo'];

    protected function casts(): array
    {
        return [
            'limite_credito' => 'decimal:2',
            'descuento_mano_obra' => 'decimal:2',
            'descuento_refacciones' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

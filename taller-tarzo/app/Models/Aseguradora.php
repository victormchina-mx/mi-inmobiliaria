<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Aseguradora extends Model
{
    use LogsActivity;

    protected $table = 'aseguradoras';

    protected $fillable = ['nombre', 'rfc', 'razon_social', 'regimen_fiscal', 'codigo_postal', 'uso_cfdi', 'telefono', 'email', 'email_facturacion', 'dias_pago', 'notas', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tabulador(): HasOne
    {
        return $this->hasOne(Tabulador::class);
    }

    public function polizas(): HasMany
    {
        return $this->hasMany(Poliza::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

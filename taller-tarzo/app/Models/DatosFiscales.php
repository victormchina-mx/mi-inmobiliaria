<?php

namespace App\Models;

use App\Support\Rfc;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DatosFiscales extends Model
{
    use LogsActivity;

    protected $table = 'datos_fiscales';

    protected $fillable = ['cliente_id', 'rfc', 'nombre_fiscal', 'regimen_fiscal', 'codigo_postal', 'uso_cfdi', 'email_facturacion', 'constancia_path'];

    protected function rfc(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor === null ? null : Rfc::normalizar($valor));
    }

    /**
     * El CFDI 4.0 exige el nombre exactamente como en la constancia, en mayúsculas y sin régimen societario.
     */
    protected function nombreFiscal(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor === null ? null : mb_strtoupper(trim($valor)));
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function regimen(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class, 'regimen_fiscal', 'clave');
    }

    public function uso(): BelongsTo
    {
        return $this->belongsTo(UsoCfdi::class, 'uso_cfdi', 'clave');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

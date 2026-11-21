<?php

namespace App\Models;

use App\Support\Vin;
use Database\Factories\VehiculoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehiculo extends Model
{
    /** @use HasFactory<VehiculoFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'vehiculos';

    protected $fillable = ['cliente_id', 'vin', 'placas', 'marca', 'modelo', 'anio', 'version', 'color', 'motor', 'transmision', 'kilometraje', 'notas'];

    protected function casts(): array
    {
        return ['anio' => 'integer', 'kilometraje' => 'integer'];
    }

    protected function vin(): Attribute
    {
        return Attribute::set(fn (?string $valor) => blank($valor) ? null : Vin::normalizar($valor));
    }

    protected function placas(): Attribute
    {
        return Attribute::set(fn (?string $valor) => blank($valor) ? null : strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $valor)));
    }

    protected function descripcion(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->marca} {$this->modelo} {$this->anio}").($this->placas ? " · {$this->placas}" : ''));
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function polizas(): HasMany
    {
        return $this->hasMany(Poliza::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $limpio = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $termino));

        return $query->where(fn (Builder $q) => $q
            ->where('placas', 'like', "%{$limpio}%")
            ->orWhere('vin', 'like', "%{$limpio}%"));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

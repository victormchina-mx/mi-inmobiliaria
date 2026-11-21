<?php

namespace App\Models;

use App\Enums\CanalPreferido;
use App\Enums\TipoPersona;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_persona', 'nombre', 'apellidos', 'telefono', 'whatsapp', 'email',
        'calle', 'colonia', 'municipio', 'estado', 'codigo_postal',
        'canal_preferido', 'acepta_promociones', 'flotilla_id', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'tipo_persona' => TipoPersona::class,
            'canal_preferido' => CanalPreferido::class,
            'acepta_promociones' => 'boolean',
        ];
    }

    protected function nombreCompleto(): Attribute
    {
        return Attribute::get(fn () => trim($this->nombre.' '.($this->apellidos ?? '')));
    }

    public function flotilla(): BelongsTo
    {
        return $this->belongsTo(Flotilla::class);
    }

    public function datosFiscales(): HasOne
    {
        return $this->hasOne(DatosFiscales::class);
    }

    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class);
    }

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    /**
     * Búsqueda rápida por nombre, teléfono, correo o RFC.
     */
    public function scopeBuscar(Builder $query, string $termino): Builder
    {
        $t = '%'.trim($termino).'%';

        return $query->where(fn (Builder $q) => $q
            ->where('nombre', 'like', $t)
            ->orWhere('apellidos', 'like', $t)
            ->orWhere('telefono', 'like', $t)
            ->orWhere('whatsapp', 'like', $t)
            ->orWhere('email', 'like', $t)
            ->orWhereHas('datosFiscales', fn (Builder $f) => $f->where('rfc', 'like', $t)));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}

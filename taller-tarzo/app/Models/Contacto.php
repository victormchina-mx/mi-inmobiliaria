<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contacto extends Model
{
    protected $table = 'contactos';

    protected $fillable = ['cliente_id', 'flotilla_id', 'nombre', 'puesto', 'telefono', 'email', 'puede_aprobar'];

    protected function casts(): array
    {
        return ['puede_aprobar' => 'boolean'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function flotilla(): BelongsTo
    {
        return $this->belongsTo(Flotilla::class);
    }
}

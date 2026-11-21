<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsoCfdi extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'sat_usos_cfdi';

    protected $primaryKey = 'clave';

    protected $keyType = 'string';

    protected $fillable = ['clave', 'descripcion', 'persona_fisica', 'persona_moral'];

    protected function casts(): array
    {
        return ['persona_fisica' => 'boolean', 'persona_moral' => 'boolean'];
    }
}

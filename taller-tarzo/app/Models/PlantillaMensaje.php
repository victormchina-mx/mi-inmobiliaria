<?php

namespace App\Models;

use App\Enums\EventoMensaje;
use Illuminate\Database\Eloquent\Model;

class PlantillaMensaje extends Model
{
    protected $table = 'plantillas_mensaje';

    protected $fillable = ['evento', 'canal', 'asunto', 'cuerpo', 'plantilla_meta', 'idioma', 'variables_meta', 'activo'];

    protected function casts(): array
    {
        return ['evento' => EventoMensaje::class, 'variables_meta' => 'array', 'activo' => 'boolean'];
    }

    /**
     * Sustituye {variable} por su valor. Las variables desconocidas se dejan vacías.
     *
     * @param  array<string, string|null>  $variables
     */
    public static function render(?string $texto, array $variables): string
    {
        return trim(preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($variables[$m[1]] ?? ''), (string) $texto));
    }
}

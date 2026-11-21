<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Avatar SVG con iniciales generado en el servidor.
 * Sustituye al proveedor por defecto (ui-avatars.com) para no enviar nombres a terceros.
 */
class Iniciales implements AvatarProvider
{
    private const COLORES = ['#0d7a4a', '#2b5f8a', '#7a4fb0', '#a15c07', '#b8336a', '#3f5560'];

    public function get(Model $record): string
    {
        $nombre = (string) Filament::getNameForDefaultAvatar($record);

        $iniciales = Str::of($nombre)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->implode('');

        $color = self::COLORES[abs(crc32($nombre)) % count(self::COLORES)];
        $texto = htmlspecialchars($iniciales ?: '?', ENT_XML1);

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="{$color}"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="system-ui,sans-serif" font-size="26" font-weight="600" fill="#fff">{$texto}</text></svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}

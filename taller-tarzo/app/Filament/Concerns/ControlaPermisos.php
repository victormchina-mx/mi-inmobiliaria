<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Conecta un Resource de Filament con la matriz de permisos ({modulo}.ver / {modulo}.gestionar).
 * Cada Resource define: protected static string $modulo = 'catalogos';
 */
trait ControlaPermisos
{
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can(static::$modulo.'.ver');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can(static::$modulo.'.gestionar');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDeleteAny(): bool
    {
        return static::canCreate();
    }
}

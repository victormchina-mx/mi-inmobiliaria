<?php

namespace App\Filament\Resources\Checkins;

use App\Filament\Resources\Checkins\Pages\EditCheckin;
use App\Filament\Resources\Checkins\Schemas\CheckinForm;
use App\Models\Checkin;
use App\Models\OrdenServicio;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Inventario de ingreso de una orden. No aparece en el menú: se abre desde la orden.
 */
class CheckinResource extends Resource
{
    protected static ?string $model = Checkin::class;

    protected static ?string $slug = 'checkin';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'check-in';

    protected static ?string $pluralModelLabel = 'Check-in';

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) auth()->user()?->can('recepcion.gestionar');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CheckinForm::configure($schema);
    }

    /**
     * Abre (o crea) el check-in de la orden y devuelve su URL.
     */
    public static function urlParaOrden(OrdenServicio $orden): string
    {
        $checkin = $orden->checkin ?? $orden->checkin()->create([
            'user_id' => auth()->id(),
            'kilometraje' => $orden->kilometraje_ingreso,
            'inventario' => CheckinForm::INVENTARIO_HABITUAL,
        ]);

        return static::getUrl('edit', ['record' => $checkin]);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditCheckin::route('/{record}'),
        ];
    }
}

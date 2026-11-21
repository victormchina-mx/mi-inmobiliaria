<?php

namespace App\Filament\Resources\ReglasMantenimiento;

use App\Filament\Concerns\ControlaPermisos;
use App\Filament\Resources\ReglasMantenimiento\Pages\ManageReglasMantenimiento;
use App\Models\ReglaMantenimiento;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ReglaMantenimientoResource extends Resource
{
    use ControlaPermisos;

    protected static string $modulo = 'crm';

    protected static ?string $model = ReglaMantenimiento::class;

    protected static ?string $slug = 'reglas-mantenimiento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 9;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $modelLabel = 'regla de mantenimiento';

    protected static ?string $pluralModelLabel = 'Reglas de mantenimiento';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Servicio')->required()->maxLength(120)->columnSpanFull(),
            TextInput::make('cada_km')->label('Cada (km)')->numeric()->minValue(500)->requiredWithout('cada_meses'),
            TextInput::make('cada_meses')->label('Cada (meses)')->numeric()->minValue(1)->maxValue(60)->requiredWithout('cada_km'),
            Toggle::make('activo')->label('Activa')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Servicio')->searchable(),
                TextColumn::make('cada_km')->label('Cada')->numeric()->suffix(' km')->placeholder('—'),
                TextColumn::make('cada_meses')->label('O cada')->suffix(' meses')->placeholder('—'),
                IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReglasMantenimiento::route('/')];
    }
}

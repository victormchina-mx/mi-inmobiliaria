<?php

namespace App\Filament\Resources\Clientes\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactosRelationManager extends RelationManager
{
    protected static string $relationship = 'contactos';

    protected static ?string $title = 'Contactos';

    protected static ?string $modelLabel = 'contacto';

    /** Editable también desde la página de consulta (Filament las deja en solo lectura por defecto). */
    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('recepcion.ver');
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('recepcion.gestionar');
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate();
    }

    public static function formulario(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('nombre')->label('Nombre')->required()->maxLength(160),
            TextInput::make('puesto')->label('Puesto')->maxLength(120),
            TextInput::make('telefono')->label('Teléfono')->tel()->maxLength(20),
            TextInput::make('email')->label('Correo')->email(),
            Toggle::make('puede_aprobar')->label('Puede autorizar presupuestos'),
        ]);
    }

    public static function tabla(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')->label('Nombre'),
                TextColumn::make('puesto')->label('Puesto')->placeholder('—'),
                TextColumn::make('telefono')->label('Teléfono')->placeholder('—')->copyable(),
                TextColumn::make('email')->label('Correo')->placeholder('—'),
                IconColumn::make('puede_aprobar')->label('Autoriza')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function form(Schema $schema): Schema
    {
        return self::formulario($schema);
    }

    public function table(Table $table): Table
    {
        return self::tabla($table);
    }
}

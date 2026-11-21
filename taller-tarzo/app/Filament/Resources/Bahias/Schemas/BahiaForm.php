<?php

namespace App\Filament\Resources\Bahias\Schemas;

use App\Enums\TipoBahia;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BahiaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nombre')->label('Nombre')->placeholder('Bahía 3, Cabina 1…')->required()->maxLength(80)->unique(ignoreRecord: true),
            Select::make('tipo')->label('Tipo')->options(TipoBahia::class)->default(TipoBahia::Bahia)->required(),
            Select::make('area_id')->label('Área')->relationship('area', 'nombre')->preload()->searchable(),
            Toggle::make('activo')->label('Disponible')->default(true),
        ]);
    }
}

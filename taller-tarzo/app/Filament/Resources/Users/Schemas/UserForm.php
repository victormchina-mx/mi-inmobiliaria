<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Rol;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre completo')->required()->maxLength(255),
            TextInput::make('email')->label('Correo electrónico')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('telefono')->label('Teléfono / WhatsApp')->tel()->maxLength(20),
            Select::make('roles')->label('Rol')
                ->relationship('roles', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record) => Rol::tryFrom($record->name)?->getLabel() ?? $record->name)
                ->multiple()->preload()->required(),
            TextInput::make('password')->label('Contraseña')
                ->password()->revealable()
                ->rule(Password::default())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Déjala vacía para conservar la actual.' : null),
            Toggle::make('activo')->label('Puede entrar al sistema')->default(true)
                ->disabled(fn ($record) => $record?->is(auth()->user())),
        ]);
    }
}

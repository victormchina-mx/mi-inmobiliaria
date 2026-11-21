<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Rol;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('roles.name')->label('Rol')->badge()
                    ->formatStateUsing(fn (string $state) => Rol::tryFrom($state)?->getLabel() ?? $state),
                IconColumn::make('app_authentication_secret')->label('2FA')->boolean()
                    ->getStateUsing(fn ($record) => filled($record->app_authentication_secret)),
                IconColumn::make('activo')->label('Activo')->boolean(),
                TextColumn::make('ultimo_acceso_at')->label('Último acceso')->since()->placeholder('Nunca')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Rol')->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => Rol::tryFrom($record->name)?->getLabel() ?? $record->name),
                TernaryFilter::make('activo')->label('Activo'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()]);
    }
}

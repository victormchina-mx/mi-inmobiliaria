<?php

namespace App\Filament\Resources\Recordatorios\Pages;

use App\Actions\Postventa\GenerarRecordatorios;
use App\Filament\Resources\Recordatorios\RecordatorioResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageRecordatorios extends ManageRecords
{
    protected static string $resource = RecordatorioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generar')->label('Calcular recordatorios del mes')->icon(Heroicon::OutlinedCalculator)
                ->visible(fn () => auth()->user()->can('crm.gestionar'))
                ->modalDescription('Se calcula automáticamente el día 1 de cada mes. No duplica los que ya existen.')
                ->action(function () {
                    $n = app(GenerarRecordatorios::class)();
                    Notification::make()->title("Recordatorios nuevos: {$n}")->success()->send();
                }),
        ];
    }
}

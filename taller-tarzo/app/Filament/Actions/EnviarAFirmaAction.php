<?php

namespace App\Filament\Actions;

use App\Actions\Recepcion\EnlaceFirma;
use App\Filament\Resources\Checkins\Pages\EditCheckin;
use App\Models\Checkin;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Genera el enlace de firma del inventario y abre WhatsApp con el mensaje listo.
 */
class EnviarAFirmaAction
{
    public static function make(\Closure $checkin): Action
    {
        return Action::make('enviarFirma')->label('Enviar a firma')->icon(Heroicon::OutlinedPaperAirplane)->color('gray')
            ->visible(function ($livewire) use ($checkin) {
                $c = $checkin();

                // Fuera de la pantalla del check-in solo se ofrece si el inventario ya se guardó.
                return auth()->user()?->can('recepcion.gestionar') && $c instanceof Checkin && ! $c->estaFirmado()
                    && ($livewire instanceof EditCheckin || $c->completado_at);
            })
            // En la pantalla del check-in, guarda primero lo capturado: el cliente firma lo que ve guardado.
            // Si falta un dato obligatorio, la validación lo marca en el formulario y el enlace no se genera.
            ->mountUsing(function ($livewire) {
                if ($livewire instanceof EditCheckin) {
                    $livewire->save(shouldRedirect: false, shouldSendSavedNotification: false);
                }
            })
            ->modalHeading('Enviar el inventario al cliente para firmar')
            ->modalDescription('El cliente revisa el inventario y firma desde su celular, sin crear cuenta. El enlace vence en '.EnlaceFirma::HORAS_VIGENCIA.' horas.')
            ->schema(fn (Schema $schema) => $schema->components([
                TextInput::make('enlace')->label('Enlace para firmar')->readOnly()
                    ->copyable(copyMessage: 'Enlace copiado')
                    ->default(fn () => EnlaceFirma::generar($checkin())),
                Text::make(fn () => $checkin()->orden->cliente->numeroWhatsapp()
                    ? 'Se abrirá WhatsApp con el mensaje listo para '.$checkin()->orden->cliente->nombre_completo.'.'
                    : 'El cliente no tiene teléfono registrado: copia el enlace y envíalo por otro medio.'),
            ]))
            ->modalSubmitActionLabel('Abrir WhatsApp')
            ->modalSubmitAction(fn (Action $action) => $checkin()->orden->cliente->numeroWhatsapp() ? $action : false)
            ->action(fn ($livewire) => $livewire->redirect(EnlaceFirma::whatsapp($checkin())));
    }
}

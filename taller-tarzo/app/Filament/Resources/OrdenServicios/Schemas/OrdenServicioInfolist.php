<?php

namespace App\Filament\Resources\OrdenServicios\Schemas;

use App\Enums\TipoOrden;
use App\Filament\Resources\Clientes\ClienteResource;
use App\Filament\Resources\Vehiculos\VehiculoResource;
use App\Models\OrdenServicio;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrdenServicioInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Orden')->columns(4)->columnSpan(2)->schema([
                TextEntry::make('folio')->label('Folio')->weight('bold')->copyable(),
                TextEntry::make('tipo')->label('Tipo')->badge(),
                TextEntry::make('etapa.nombre')->label('Etapa')->badge()->color(fn (OrdenServicio $record) => $record->etapa?->color),
                TextEntry::make('etapa_desde')->label('En esta etapa desde')->since(),
                TextEntry::make('cliente.nombre_completo')->label('Cliente')
                    ->url(fn (OrdenServicio $record) => ClienteResource::getUrl('view', ['record' => $record->cliente_id])),
                TextEntry::make('cliente.telefono')->label('Teléfono')->placeholder('—')->copyable(),
                TextEntry::make('vehiculo.descripcion')->label('Vehículo')->columnSpan(2)
                    ->url(fn (OrdenServicio $record) => VehiculoResource::getUrl('view', ['record' => $record->vehiculo_id])),
                TextEntry::make('fecha_ingreso')->label('Ingreso')->dateTime('d/m/Y H:i'),
                TextEntry::make('fecha_promesa')->label('Promesa de entrega')->dateTime('d/m/Y H:i')->placeholder('Sin definir'),
                TextEntry::make('kilometraje_ingreso')->label('Km de ingreso')->numeric()->suffix(' km')->placeholder('—'),
                TextEntry::make('asesor.name')->label('Asesor')->placeholder('—'),
                TextEntry::make('aseguradora.nombre')->label('Aseguradora')->placeholder('—')
                    ->visible(fn (OrdenServicio $record) => $record->aseguradora_id !== null),
                TextEntry::make('numero_siniestro')->label('Siniestro')->placeholder('—')->copyable()
                    ->visible(fn (OrdenServicio $record) => $record->aseguradora_id !== null),
                TextEntry::make('poliza.numero')->label('Póliza')->placeholder('—')
                    ->visible(fn (OrdenServicio $record) => $record->aseguradora_id !== null),
                TextEntry::make('motivo_ingreso')->label('Motivo de ingreso')->columnSpanFull(),
            ]),

            Section::make('Check-in')->columnSpan(1)->schema([
                ViewEntry::make('checkin')->hiddenLabel()->view('filament.checkin.resumen'),
            ]),

            Section::make('Siniestro')->columns(4)->columnSpanFull()
                ->visible(fn (OrdenServicio $record) => $record->tipo === TipoOrden::Seguro)
                ->schema([
                    TextEntry::make('siniestro.numero')->label('Número')->placeholder('Sin capturar'),
                    TextEntry::make('siniestro.estatus')->label('Estatus')->badge()->placeholder('—'),
                    TextEntry::make('siniestro.ajustador_nombre')->label('Ajustador')->placeholder('—'),
                    TextEntry::make('deducible')->label('Deducible')->money('MXN')
                        ->state(fn (OrdenServicio $record) => $record->siniestro?->deducibleMonto())->placeholder('—'),
                ]),

            Section::make('Historial de etapas')->collapsible()->collapsed()->columnSpanFull()->schema([
                RepeatableEntry::make('historialEtapas')->hiddenLabel()->contained(false)
                    ->table([
                        RepeatableEntry\TableColumn::make('Fecha'),
                        RepeatableEntry\TableColumn::make('De'),
                        RepeatableEntry\TableColumn::make('A'),
                        RepeatableEntry\TableColumn::make('Usuario'),
                        RepeatableEntry\TableColumn::make('Comentario'),
                    ])
                    ->schema([
                        TextEntry::make('created_at')->dateTime('d/m/Y H:i'),
                        TextEntry::make('etapaAnterior.nombre')->placeholder('—'),
                        TextEntry::make('etapa.nombre')->badge(),
                        TextEntry::make('usuario.name')->placeholder('Sistema'),
                        TextEntry::make('comentario')->placeholder('—')
                            ->suffix(fn ($record) => $record->excepcion ? ' (excepción)' : ''),
                    ]),
            ]),
        ]);
    }
}

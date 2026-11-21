<?php

namespace App\Filament\Resources\Cotizaciones\Schemas;

use App\Enums\CalidadRefaccion;
use App\Enums\Disponibilidad;
use App\Enums\EstadoPartida;
use App\Enums\NivelDano;
use App\Enums\OperacionHyP;
use App\Enums\Paga;
use App\Enums\TipoPartida;
use App\Enums\TipoPintura;
use App\Filament\Resources\Cotizaciones\Support\ContextoFormulario;
use App\Models\Area;
use App\Models\Cotizacion;
use App\Models\Operacion;
use App\Models\Parametro;
use App\Models\PiezaCarroceria;
use App\Services\Cotizacion\Cotizador;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Livewire\Component as Livewire;

class CotizacionForm
{
    /** Campos de una partida que influyen en el cálculo. */
    private const CAMPOS_CALCULO = [
        'tipo', 'paga', 'estado', 'area_id', 'horas', 'pieza_id', 'operacion_hyp', 'nivel_dano', 'tipo_pintura',
        'horas_laminado', 'horas_pintura', 'materiales_manual', 'cantidad', 'costo_unitario', 'margen', 'precio_manual',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Datos generales')->columns(4)->schema([
                Text::make(fn (Livewire $livewire) => self::resumenOrden(self::cotizacion($livewire)))->columnSpan(3),
                TextInput::make('vigencia_dias')->label('Vigencia')->numeric()->minValue(1)->suffix('días')->required(),
                Textarea::make('notas')->label('Notas para el cliente')->rows(2)->columnSpanFull()
                    ->placeholder('Tiempo estimado de reparación, condiciones, garantía…'),
            ]),

            Section::make('Partidas')->schema([
                Repeater::make('partidas')->hiddenLabel()
                    ->relationship()
                    ->orderColumn('orden')
                    ->schema(self::camposPartida())
                    ->itemLabel(fn (array $state) => self::etiquetaPartida($state))
                    ->collapsible()
                    ->cloneable()
                    ->addActionLabel('Agregar partida')
                    ->defaultItems(0)
                    ->live()
                    ->deleteAction(fn (Action $action) => $action->hidden(
                        fn (array $arguments, Repeater $component) => ($component->getRawItemState($arguments['item'])['estado'] ?? 'pendiente') !== EstadoPartida::Pendiente->value
                    ))
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => self::limpiarSegunTipo($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data) => self::limpiarSegunTipo($data)),
            ]),

            Section::make('Totales')->schema([
                View::make('filament.cotizacion.totales')
                    ->viewData(fn (Get $get, Livewire $livewire) => self::datosTotales($get('partidas') ?? [], self::cotizacion($livewire))),
            ]),
        ])->disabled(fn (Livewire $livewire) => ! self::cotizacion($livewire)?->estado->editable());
    }

    /**
     * @return array<int, mixed>
     */
    private static function camposPartida(): array
    {
        $es = fn (TipoPartida ...$tipos) => fn (Get $get) => in_array(self::valor($get('tipo')), array_map(fn ($t) => $t->value, $tipos), true);
        $op = fn (Get $get) => OperacionHyP::tryFrom((string) self::valor($get('operacion_hyp'))) ?? OperacionHyP::Reparar;
        $calcula = fn ($campo) => $campo->live(onBlur: true);

        return [
            Hidden::make('estado')->dehydrated(false),

            Group::make()->disabled(fn (Get $get) => self::bloqueada($get))->columns(6)->schema([
                ToggleButtons::make('tipo')->label('Tipo')->options(TipoPartida::class)->inline()->required()->live()
                    ->default(TipoPartida::ManoObra->value)->columnSpan(4),
                ToggleButtons::make('paga')->label('Paga')->options(Paga::class)->inline()->required()->live()
                    ->default(fn (Livewire $livewire) => self::cotizacion($livewire)?->esSeguro() ? Paga::Aseguradora->value : Paga::Cliente->value)
                    ->visible(fn (Livewire $livewire) => (bool) self::cotizacion($livewire)?->esSeguro())
                    ->columnSpan(2),

                // Mano de obra
                Select::make('operacion_id')->label('Operación del catálogo')
                    ->options(fn () => Operacion::with('area')->where('activo', true)->orderBy('nombre')->get()
                        ->groupBy(fn ($o) => $o->area->nombre)
                        ->map(fn ($g) => $g->mapWithKeys(fn ($o) => [$o->id => "{$o->nombre} ({$o->horas_estandar} h)"]))->all())
                    ->searchable()->live()
                    ->afterStateUpdated(function (?string $state, Set $set) {
                        if ($o = Operacion::find($state)) {
                            $set('descripcion', $o->nombre);
                            $set('area_id', $o->area_id);
                            $set('horas', (float) $o->horas_estandar);
                        }
                    })
                    ->visible($es(TipoPartida::ManoObra))->columnSpan(3),
                Select::make('area_id')->label('Área')->options(fn () => Area::orderBy('nombre')->pluck('nombre', 'id'))
                    ->live()->required($es(TipoPartida::ManoObra))->visible($es(TipoPartida::ManoObra))->columnSpan(2),
                $calcula(TextInput::make('horas')->label('Horas')->numeric()->minValue(0)->step(0.1))
                    ->required($es(TipoPartida::ManoObra))->visible($es(TipoPartida::ManoObra))->columnSpan(1),

                // Hojalatería y pintura
                Select::make('pieza_id')->label('Pieza')->options(fn () => PiezaCarroceria::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()->live()->required($es(TipoPartida::HojalateriaPintura))
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                        if ($p = PiezaCarroceria::find($state)) {
                            $set('descripcion', $p->nombre);
                            $set('horas_laminado', $p->horas_laminado_ref);
                            $set('horas_pintura', $p->horas_pintura_ref);
                        }
                    })
                    ->visible($es(TipoPartida::HojalateriaPintura))->columnSpan(2),
                ToggleButtons::make('operacion_hyp')->label('Operación')->options(OperacionHyP::class)->inline()->live()
                    ->default(OperacionHyP::Reparar->value)->required($es(TipoPartida::HojalateriaPintura))
                    ->visible($es(TipoPartida::HojalateriaPintura))->columnSpan(4),
                ToggleButtons::make('nivel_dano')->label('Nivel de daño')->options(NivelDano::class)->inline()->live()
                    ->default(NivelDano::Medio->value)
                    ->visible(fn (Get $get) => $es(TipoPartida::HojalateriaPintura)($get) && $op($get) === OperacionHyP::Reparar)->columnSpan(2),
                ToggleButtons::make('tipo_pintura')->label('Pintura')->options(TipoPintura::class)->inline()->live()
                    ->default(TipoPintura::Solida->value)
                    ->visible(fn (Get $get) => $es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaPintura())->columnSpan(2),
                $calcula(TextInput::make('horas_laminado')->label('Horas laminado')->numeric()->minValue(0)->step(0.1))
                    ->visible($es(TipoPartida::HojalateriaPintura))->columnSpan(1),
                $calcula(TextInput::make('horas_pintura')->label('Horas pintura')->numeric()->minValue(0)->step(0.1))
                    ->visible($es(TipoPartida::HojalateriaPintura))->columnSpan(1),
                $calcula(TextInput::make('materiales_manual')->label('Materiales (ajuste)')->numeric()->minValue(0)->prefix('$')
                    ->placeholder(fn (Get $get, Livewire $livewire) => 'Calculado: $'.number_format(self::calcularItem($get, $livewire)['importe_materiales'] ?? 0, 2))
                    ->helperText('Vacío = cálculo automático por m².'))
                    ->visible(fn (Get $get) => $es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaPintura())->columnSpan(2),

                // Descripción común
                TextInput::make('descripcion')->label('Concepto')->required()->maxLength(255)->columnSpan(6),

                // Refacciones, otros y pieza nueva en HyP
                $calcula(TextInput::make('cantidad')->label('Cantidad')->numeric()->minValue(0.01)->default(1))
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion, TipoPartida::Otro)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(1),
                TextInput::make('numero_parte')->label('No. de parte')->maxLength(60)
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(2),
                $calcula(TextInput::make('costo_unitario')->label('Costo')->numeric()->minValue(0)->prefix('$'))
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion, TipoPartida::Otro)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(1),
                $calcula(TextInput::make('margen')->label('Margen')->numeric()->minValue(0)->suffix('%')
                    ->placeholder(fn () => (string) Parametro::valor('margen_refacciones', 30)))
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion, TipoPartida::Otro)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(1),
                $calcula(TextInput::make('precio_manual')->label('Precio fijo')->numeric()->minValue(0)->prefix('$')
                    ->helperText('Opcional: ignora costo y margen.'))
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion, TipoPartida::Otro)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(1),
                ToggleButtons::make('calidad')->label('Calidad')->options(CalidadRefaccion::class)->inline()
                    ->visible(fn (Get $get) => $es(TipoPartida::Refaccion)($get) || ($es(TipoPartida::HojalateriaPintura)($get) && $op($get)->llevaRefaccion()))
                    ->columnSpan(3),
                Select::make('disponibilidad')->label('Disponibilidad')->options(Disponibilidad::class)
                    ->visible($es(TipoPartida::Refaccion))->columnSpan(1),
                TextInput::make('proveedor')->label('Proveedor')->maxLength(120)
                    ->visible($es(TipoPartida::Refaccion, TipoPartida::Otro))->columnSpan(2),
            ]),

            Grid::make(2)->schema([
                Text::make(fn (Get $get) => self::bloqueada($get)
                    ? 'Partida '.strtolower(EstadoPartida::from(self::valor($get('estado')))->getLabel()).' en una versión anterior: no se puede modificar.'
                    : '')
                    ->color('warning'),
                Text::make(fn (Get $get, Livewire $livewire) => 'Importe: $'.number_format(self::calcularItem($get, $livewire)['importe'] ?? 0, 2))
                    ->weight('bold'),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function calcularItem(Get $get, Livewire $livewire): array
    {
        $contexto = ContextoFormulario::para(self::cotizacion($livewire));

        if (! $contexto) {
            return [];
        }

        $estado = [];
        foreach (self::CAMPOS_CALCULO as $campo) {
            $estado[$campo] = $get($campo);
        }

        return app(Cotizador::class)->calcularPartida($estado, $contexto);
    }

    /**
     * @param  array<string, array<string, mixed>>  $partidas
     * @return array<string, mixed>
     */
    private static function datosTotales(array $partidas, ?Cotizacion $cotizacion): array
    {
        $contexto = ContextoFormulario::para($cotizacion);

        if (! $contexto) {
            return ['t' => null, 'seguro' => false];
        }

        return [
            't' => app(Cotizador::class)->previsualizar($partidas, $contexto)['totales'],
            'seguro' => $cotizacion->esSeguro(),
            'contexto' => $contexto,
            'siniestroCapturado' => (bool) $cotizacion->orden?->siniestro,
        ];
    }

    private static function etiquetaPartida(array $state): string
    {
        $tipo = TipoPartida::tryFrom((string) self::valor($state['tipo'] ?? null));
        $estado = EstadoPartida::tryFrom((string) self::valor($state['estado'] ?? null));

        return trim(($state['descripcion'] ?? '') ?: 'Nueva partida')
            .($tipo ? ' · '.$tipo->getLabel() : '')
            .($estado && $estado !== EstadoPartida::Pendiente ? ' · '.$estado->getLabel() : '');
    }

    private static function bloqueada(Get $get): bool
    {
        $estado = self::valor($get('estado'));

        return $estado !== null && $estado !== EstadoPartida::Pendiente->value;
    }

    /**
     * Borra los campos que no aplican al tipo de partida para no guardar datos inconsistentes.
     */
    private static function limpiarSegunTipo(array $data): array
    {
        $tipo = TipoPartida::tryFrom((string) self::valor($data['tipo'] ?? null));
        $op = OperacionHyP::tryFrom((string) self::valor($data['operacion_hyp'] ?? null));

        $nulos = match ($tipo) {
            TipoPartida::ManoObra => ['pieza_id', 'operacion_hyp', 'nivel_dano', 'tipo_pintura', 'horas_laminado', 'horas_pintura', 'materiales_manual', 'costo_unitario', 'margen', 'precio_manual', 'numero_parte', 'calidad', 'disponibilidad', 'proveedor'],
            TipoPartida::Refaccion, TipoPartida::Otro => ['operacion_id', 'area_id', 'horas', 'pieza_id', 'operacion_hyp', 'nivel_dano', 'tipo_pintura', 'horas_laminado', 'horas_pintura', 'materiales_manual'],
            TipoPartida::HojalateriaPintura => array_merge(
                ['operacion_id', 'area_id', 'horas', 'disponibilidad', 'proveedor'],
                $op === OperacionHyP::Sustituir ? [] : ['costo_unitario', 'margen', 'precio_manual', 'numero_parte', 'calidad'],
                $op === OperacionHyP::Reparar ? [] : ['nivel_dano'],
                $op?->llevaPintura() ?? true ? [] : ['tipo_pintura', 'materiales_manual'],
            ),
            default => [],
        };

        foreach ($nulos as $campo) {
            $data[$campo] = null;
        }

        if (blank($data['cantidad'] ?? null)) {
            $data['cantidad'] = 1;
        }

        return $data;
    }

    private static function resumenOrden(?Cotizacion $c): string
    {
        if (! $c) {
            return '';
        }

        $o = $c->orden;
        $texto = "Orden {$o->folio} · {$o->vehiculo->descripcion} · {$o->cliente->nombre_completo}";

        return $c->esSeguro()
            ? $texto.' · Seguro: '.$c->aseguradora?->nombre.($o->siniestro?->numero ? " (siniestro {$o->siniestro->numero})" : '')
            : $texto;
    }

    private static function cotizacion(Livewire $livewire): ?Cotizacion
    {
        return method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
    }

    private static function valor(mixed $v): ?string
    {
        return $v instanceof BackedEnum ? (string) $v->value : (filled($v) ? (string) $v : null);
    }
}

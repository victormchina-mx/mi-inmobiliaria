<?php

namespace App\Services\Cotizacion;

use App\Enums\NivelDano;
use App\Enums\TipoPintura;
use App\Models\Area;
use App\Models\Cotizacion;
use App\Models\Parametro;
use App\Models\PiezaCarroceria;
use App\Models\Tabulador;

/**
 * Todo lo que el cotizador necesita saber para calcular: tarifas, descuentos,
 * costos de materiales, cabina, IVA y montos del siniestro.
 */
final class Contexto
{
    /**
     * @param  array<int, float>  $tarifasArea  area_id => tarifa por hora
     * @param  array<string, float>  $costoM2  tipo de pintura => costo de materiales por m²
     * @param  array<string, float>  $factorDano  nivel => factor
     * @param  array<int, float>  $superficies  pieza_id => m²
     */
    public function __construct(
        public array $tarifasArea,
        public ?int $areaLaminado,
        public ?int $areaPintura,
        public array $costoM2,
        public array $factorDano,
        public array $superficies,
        public float $margenDefecto = 30,
        public float $ivaTasa = 0.16,
        public float $tarifaCicloCabina = 0,
        public int $piezasPorCiclo = 3,
        public bool $esSeguro = false,
        public float $descuentoRefacciones = 0,
        public float $descuentoManoObra = 0,
        public float $factorMateriales = 1,
        public float $deducible = 0,
        public float $demerito = 0,
        public float $salvamento = 0,
    ) {}

    public function tarifa(?int $areaId): float
    {
        return $areaId ? ($this->tarifasArea[$areaId] ?? 0) : 0;
    }

    public static function paraCotizacion(Cotizacion $cotizacion): self
    {
        $areas = Area::query()->get(['id', 'clave', 'tarifa_hora']);
        $tarifas = $areas->mapWithKeys(fn (Area $a) => [$a->id => (float) $a->tarifa_hora])->all();

        $tabulador = $cotizacion->aseguradora_id
            ? Tabulador::with('tarifas')->firstWhere('aseguradora_id', $cotizacion->aseguradora_id)
            : null;

        foreach ($tabulador?->tarifas ?? [] as $t) {
            $tarifas[$t->area_id] = (float) $t->tarifa_hora;
        }

        $siniestro = $cotizacion->esSeguro() ? $cotizacion->orden?->siniestro : null;

        return new self(
            tarifasArea: $tarifas,
            areaLaminado: $areas->firstWhere('clave', 'hojalateria')?->id,
            areaPintura: $areas->firstWhere('clave', 'pintura')?->id,
            costoM2: collect(TipoPintura::cases())->mapWithKeys(fn (TipoPintura $t) => [$t->value => Parametro::valor("costo_m2_{$t->value}")])->all(),
            factorDano: collect(NivelDano::cases())->mapWithKeys(fn (NivelDano $n) => [$n->value => Parametro::valor("factor_dano_{$n->value}", 1)])->all(),
            superficies: PiezaCarroceria::query()->pluck('superficie_m2', 'id')->map(fn ($v) => (float) $v)->all(),
            margenDefecto: Parametro::valor('margen_refacciones', 30),
            ivaTasa: $cotizacion->iva_tasa === null ? 0.16 : (float) $cotizacion->iva_tasa,
            tarifaCicloCabina: Parametro::valor('tarifa_ciclo_cabina'),
            piezasPorCiclo: max(1, (int) Parametro::valor('piezas_por_ciclo_cabina', 3)),
            esSeguro: $cotizacion->esSeguro(),
            descuentoRefacciones: $tabulador?->descuento_refacciones ?? 0,
            descuentoManoObra: $tabulador?->descuento_mano_obra ?? 0,
            factorMateriales: $tabulador?->factor_materiales ?? 1,
            deducible: $siniestro?->deducibleMonto() ?? 0,
            demerito: (float) ($siniestro?->demerito ?? 0),
            salvamento: (float) ($siniestro?->salvamento ?? 0),
        );
    }
}

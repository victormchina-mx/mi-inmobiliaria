<?php

namespace App\Services\Cotizacion;

use App\Enums\EstadoPartida;
use App\Enums\OperacionHyP;
use App\Enums\Paga;
use App\Enums\TipoPartida;
use App\Models\Cotizacion;
use App\Models\Partida;
use BackedEnum;
use Illuminate\Support\Facades\DB;

/**
 * Motor de cálculo de cotizaciones. Funciona igual con el estado del formulario (arrays)
 * que con partidas guardadas, para que la vista previa y lo guardado coincidan.
 *
 * Reglas (importes sin IVA):
 *  - Mano de obra:      horas × tarifa del área (tarifa del tabulador si es seguro)
 *                       − descuento de mano de obra del tabulador si paga la aseguradora.
 *  - Refacción / otro:  precio = precio manual, o costo × (1 + margen %)
 *                       − descuento de refacciones del tabulador si paga la aseguradora;
 *                       importe = cantidad × precio.
 *  - Hojalatería y pintura (por pieza):
 *        horas laminado × tarifa hojalatería + horas pintura × tarifa pintura
 *      + materiales = m² de la pieza × costo por m² del tipo de pintura × factor
 *                     (factor por nivel de daño si se repara; 1 si se sustituye o solo se pinta)
 *                     × factor de materiales del tabulador; o el monto manual del valuador
 *      + refacción nueva (cantidad × precio) si la operación es sustituir.
 *  - Cabina:  ciclos = ⌈piezas pintadas ÷ piezas por ciclo⌉ × tarifa por ciclo.
 *  - Seguro (montos con IVA):
 *        aseguradora = lo cubierto − deducible − demérito − salvamento (nunca negativo)
 *        cliente     = lo no cubierto + deducible + demérito (lo que cubra el seguro como máximo)
 *  - Las partidas rechazadas no suman.
 */
class Cotizador
{
    /**
     * @param  array<string, mixed>  $p  estado de la partida
     * @return array{tarifa_aplicada: float|null, importe_materiales: float, precio_unitario: float, importe: float}
     */
    public function calcularPartida(array $p, Contexto $c): array
    {
        $tipo = self::enum(TipoPartida::class, $p['tipo'] ?? null);
        $aseguradora = $c->esSeguro && self::enum(Paga::class, $p['paga'] ?? null) === Paga::Aseguradora;
        $descMO = $aseguradora ? $c->descuentoManoObra / 100 : 0;
        $descRef = $aseguradora ? $c->descuentoRefacciones / 100 : 0;
        $cantidad = max(0, (float) ($p['cantidad'] ?? 1)) ?: 1;

        $resultado = ['tarifa_aplicada' => null, 'importe_materiales' => 0.0, 'precio_unitario' => 0.0, 'importe' => 0.0];

        switch ($tipo) {
            case TipoPartida::ManoObra:
                $tarifa = $c->tarifa(self::id($p['area_id'] ?? null));
                $importe = (float) ($p['horas'] ?? 0) * $tarifa * (1 - $descMO);
                $resultado = ['tarifa_aplicada' => $tarifa, 'importe_materiales' => 0.0, 'precio_unitario' => self::r($importe), 'importe' => self::r($importe)];
                break;

            case TipoPartida::Refaccion:
            case TipoPartida::Otro:
                $precio = $this->precioRefaccion($p, $c) * (1 - $descRef);
                $resultado['precio_unitario'] = self::r($precio);
                $resultado['importe'] = self::r($precio * $cantidad);
                break;

            case TipoPartida::HojalateriaPintura:
                $op = self::enum(OperacionHyP::class, $p['operacion_hyp'] ?? null) ?? OperacionHyP::Reparar;
                $manoObra = ((float) ($p['horas_laminado'] ?? 0) * $c->tarifa($c->areaLaminado)
                    + (float) ($p['horas_pintura'] ?? 0) * $c->tarifa($c->areaPintura)) * (1 - $descMO);

                $materiales = $this->materiales($p, $op, $c);
                $refaccion = $op->llevaRefaccion() ? $this->precioRefaccion($p, $c) * (1 - $descRef) * $cantidad : 0;
                $importe = $manoObra + $materiales + $refaccion;

                $resultado = [
                    'tarifa_aplicada' => $c->tarifa($c->areaLaminado),
                    'importe_materiales' => self::r($materiales),
                    'precio_unitario' => self::r($importe),
                    'importe' => self::r($importe),
                ];
                break;
        }

        return $resultado;
    }

    /**
     * @param  iterable<array<string, mixed>>  $partidas  partidas con su 'importe' ya calculado
     * @return array<string, float|int>
     */
    public function totales(iterable $partidas, Contexto $c): array
    {
        $sub = ['mano_obra' => 0.0, 'refaccion' => 0.0, 'hojalateria_pintura' => 0.0, 'otro' => 0.0];
        $cubierto = 0.0;
        $noCubierto = 0.0;
        $pintadas = 0;
        $pintadasAseguradora = 0;

        foreach ($partidas as $p) {
            if (self::enum(EstadoPartida::class, $p['estado'] ?? null) === EstadoPartida::Rechazada) {
                continue;
            }

            $tipo = self::enum(TipoPartida::class, $p['tipo'] ?? null);
            if ($tipo === null) {
                continue;
            }

            $importe = (float) ($p['importe'] ?? 0);
            $sub[$tipo->value] += $importe;

            $aseguradora = $c->esSeguro && self::enum(Paga::class, $p['paga'] ?? null) === Paga::Aseguradora;
            $aseguradora ? $cubierto += $importe : $noCubierto += $importe;

            if ($tipo === TipoPartida::HojalateriaPintura
                && (self::enum(OperacionHyP::class, $p['operacion_hyp'] ?? null) ?? OperacionHyP::Reparar)->llevaPintura()) {
                $pintadas++;
                $aseguradora && $pintadasAseguradora++;
            }
        }

        $ciclos = $pintadas > 0 ? (int) ceil($pintadas / $c->piezasPorCiclo) : 0;
        $cabina = $ciclos * $c->tarifaCicloCabina;
        // La cabina la paga la aseguradora si hay alguna pieza pintada a su cargo.
        $pintadasAseguradora > 0 ? $cubierto += $cabina : $noCubierto += $cabina;

        $subtotal = array_sum($sub) + $cabina;
        $iva = $subtotal * $c->ivaTasa;
        $total = $subtotal + $iva;

        if ($c->esSeguro) {
            $brutoAseguradora = $cubierto * (1 + $c->ivaTasa);
            $aCargoCliente = min($c->deducible + $c->demerito, $brutoAseguradora);
            $montoAseguradora = max(0, $brutoAseguradora - $aCargoCliente - $c->salvamento);
            $montoCliente = $noCubierto * (1 + $c->ivaTasa) + $aCargoCliente;
        } else {
            $montoAseguradora = 0;
            $montoCliente = $total;
        }

        return [
            'subtotal_mano_obra' => self::r($sub['mano_obra']),
            'subtotal_refacciones' => self::r($sub['refaccion']),
            'subtotal_hojalateria_pintura' => self::r($sub['hojalateria_pintura']),
            'subtotal_otros' => self::r($sub['otro']),
            'ciclos_cabina' => $ciclos,
            'importe_cabina' => self::r($cabina),
            'subtotal' => self::r($subtotal),
            'iva' => self::r($iva),
            'total' => self::r($total),
            'monto_aseguradora' => self::r($montoAseguradora),
            'monto_cliente' => self::r($montoCliente),
            'deducible' => self::r($c->esSeguro ? $c->deducible : 0),
        ];
    }

    /**
     * Calcula partidas y totales a partir del estado de un formulario (vista previa).
     *
     * @param  array<int|string, array<string, mixed>>  $partidas
     * @return array{partidas: array<int|string, array<string, mixed>>, totales: array<string, float|int>}
     */
    public function previsualizar(array $partidas, Contexto $c): array
    {
        $calculadas = [];
        foreach ($partidas as $clave => $p) {
            $calculadas[$clave] = [...$p, ...$this->calcularPartida($p, $c)];
        }

        return ['partidas' => $calculadas, 'totales' => $this->totales($calculadas, $c)];
    }

    /**
     * Recalcula y guarda importes de cada partida y totales de la cotización.
     */
    public function recalcular(Cotizacion $cotizacion): Cotizacion
    {
        return DB::transaction(function () use ($cotizacion) {
            $cotizacion->load('partidas', 'orden.siniestro');
            $c = Contexto::paraCotizacion($cotizacion);

            foreach ($cotizacion->partidas as $partida) {
                $partida->forceFill($this->calcularPartida($this->estadoDe($partida), $c))->saveQuietly();
            }

            $totales = $this->totales($cotizacion->partidas->map(fn (Partida $p) => $this->estadoDe($p)), $c);
            $cotizacion->forceFill($totales)->save();

            return $cotizacion;
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function estadoDe(Partida $p): array
    {
        return [...$p->getAttributes(), 'importe' => $p->importe];
    }

    private function precioRefaccion(array $p, Contexto $c): float
    {
        if (isset($p['precio_manual']) && $p['precio_manual'] !== '' && $p['precio_manual'] !== null) {
            return (float) $p['precio_manual'];
        }

        $margen = isset($p['margen']) && $p['margen'] !== '' && $p['margen'] !== null ? (float) $p['margen'] : $c->margenDefecto;

        return (float) ($p['costo_unitario'] ?? 0) * (1 + $margen / 100);
    }

    private function materiales(array $p, OperacionHyP $op, Contexto $c): float
    {
        if (isset($p['materiales_manual']) && $p['materiales_manual'] !== '' && $p['materiales_manual'] !== null) {
            return (float) $p['materiales_manual'];
        }

        if (! $op->llevaPintura()) {
            return 0;
        }

        $superficie = $c->superficies[self::id($p['pieza_id'] ?? null)] ?? 0;
        $tipoPintura = self::valor($p['tipo_pintura'] ?? null) ?? 'solida';
        $factor = $op === OperacionHyP::Reparar
            ? ($c->factorDano[self::valor($p['nivel_dano'] ?? null) ?? 'medio'] ?? 1)
            : 1;

        return $superficie * ($c->costoM2[$tipoPintura] ?? 0) * $factor * $c->factorMateriales;
    }

    /**
     * @template T of BackedEnum
     *
     * @param  class-string<T>  $clase
     * @return T|null
     */
    private static function enum(string $clase, mixed $valor): ?BackedEnum
    {
        return $valor instanceof $clase ? $valor : (is_string($valor) && $valor !== '' ? $clase::tryFrom($valor) : null);
    }

    private static function valor(mixed $v): ?string
    {
        return $v instanceof BackedEnum ? (string) $v->value : (is_string($v) && $v !== '' ? $v : null);
    }

    private static function id(mixed $v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }

    private static function r(float $v): float
    {
        return round($v, 2);
    }
}

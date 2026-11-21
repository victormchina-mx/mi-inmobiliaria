<?php

namespace App\Filament\Resources\Productos\Widgets;

use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ResumenInventario extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $valor = (float) Producto::where('activo', true)->selectRaw('COALESCE(SUM(existencia * costo_promedio), 0) as v')->value('v');
        $bajo = Producto::where('activo', true)->bajoMinimo()->count();
        $sinMov = Producto::where('activo', true)->sinMovimiento(90)->count();

        return [
            Stat::make('Valor del inventario', Number::currency($valor))->description('A costo promedio'),
            Stat::make('Bajo el mínimo', (string) $bajo)->description($bajo ? 'Revisa y genera la orden de compra' : 'Todo en orden')
                ->color($bajo ? 'danger' : 'success'),
            Stat::make('Sin movimiento en 90 días', (string) $sinMov)->description('Dinero detenido en almacén')
                ->color($sinMov ? 'warning' : 'gray'),
        ];
    }
}

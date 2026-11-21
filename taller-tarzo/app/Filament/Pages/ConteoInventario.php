<?php

namespace App\Filament\Pages;

use App\Models\Producto;
use App\Services\Inventario\Inventario;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Conteo físico: se escanea o escribe la clave, se captura lo contado y al final se aplican los ajustes.
 * Funciona con lectores de código de barras USB o Bluetooth (escriben como teclado).
 */
class ConteoInventario extends Page
{
    protected string $view = 'filament.pages.conteo-inventario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Almacén';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Conteo físico';

    public string $codigo = '';

    /** @var array<int, array{id:int, sku:string, nombre:string, unidad:string, sistema:float, contado:float|string|null}> */
    public array $conteo = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('almacen.gestionar');
    }

    public function agregar(): void
    {
        $producto = filled($this->codigo) ? Producto::buscarCodigo($this->codigo) : null;

        if (! $producto) {
            Notification::make()->title("No se encontró \"{$this->codigo}\"")->warning()->send();
            $this->codigo = '';

            return;
        }

        if (isset($this->conteo[$producto->id])) {
            // Escanear de nuevo suma una pieza (cómodo para contar con el lector).
            $this->conteo[$producto->id]['contado'] = (float) $this->conteo[$producto->id]['contado'] + 1;
        } else {
            $this->conteo[$producto->id] = [
                'id' => $producto->id,
                'sku' => $producto->sku,
                'nombre' => $producto->nombre,
                'unidad' => $producto->unidad->getLabel(),
                'sistema' => $producto->existencia,
                'contado' => 1,
            ];
        }

        $this->codigo = '';
    }

    public function quitar(int $id): void
    {
        unset($this->conteo[$id]);
    }

    public function aplicar(): void
    {
        $ajustes = 0;

        DB::transaction(function () use (&$ajustes) {
            foreach ($this->conteo as $fila) {
                if (! is_numeric($fila['contado'])) {
                    continue;
                }
                $mov = app(Inventario::class)->ajustarA(Producto::findOrFail($fila['id']), (float) $fila['contado'], auth()->user(), 'Conteo físico '.now()->format('d/m/Y'));
                $mov && $ajustes++;
            }
        });

        Notification::make()->title("Conteo aplicado: {$ajustes} ajustes")->body(count($this->conteo) - $ajustes.' productos coincidían.')->success()->send();
        $this->conteo = [];
    }
}

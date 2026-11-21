<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use App\Models\Mensaje;
use App\Services\Comunicacion\Notificador;
use App\Services\Comunicacion\WhatsAppCloud;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Bandeja de WhatsApp: últimas conversaciones y respuesta del asesor.
 * Meta solo permite texto libre dentro de las 24 h siguientes al último mensaje del cliente.
 */
class Conversaciones extends Page
{
    protected string $view = 'filament.pages.conversaciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Postventa';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Conversaciones';

    #[Url]
    public ?int $cliente = null;

    public string $texto = '';

    public string $buscar = '';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('crm.ver');
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Mensaje::where('direccion', 'entrante')->whereNull('leido_at')->count();

        return $n ? (string) $n : null;
    }

    /**
     * Clientes con mensajes, el más reciente primero.
     */
    public function getBandeja(): Collection
    {
        $ultimos = Mensaje::query()->whereNotNull('cliente_id')
            ->selectRaw('cliente_id, max(id) as ultimo_id')
            ->groupBy('cliente_id')->orderByDesc('ultimo_id')->limit(60)
            ->when($this->buscar !== '', fn ($query) => $query->whereIn('cliente_id', Cliente::buscar($this->buscar)->select('id')))
            ->pluck('ultimo_id');

        $sinLeer = Mensaje::where('direccion', 'entrante')->whereNull('leido_at')->whereNotNull('cliente_id')
            ->selectRaw('cliente_id, count(*) as n')->groupBy('cliente_id')->pluck('n', 'cliente_id');

        return Mensaje::with('cliente')->whereIn('id', $ultimos)->orderByDesc('id')->get()
            ->each(fn (Mensaje $m) => $m->setAttribute('sin_leer', (int) ($sinLeer[$m->cliente_id] ?? 0)));
    }

    public function getClienteActual(): ?Cliente
    {
        return $this->cliente ? Cliente::find($this->cliente) : null;
    }

    public function getHilo(): Collection
    {
        if (! $this->cliente) {
            return collect();
        }

        Mensaje::where('cliente_id', $this->cliente)->where('direccion', 'entrante')->whereNull('leido_at')->update(['leido_at' => now()]);

        return Mensaje::with(['autor', 'orden'])->where('cliente_id', $this->cliente)
            ->where('estado', '!=', 'omitido')->latest('id')->limit(100)->get()->reverse()->values();
    }

    public function puedeResponder(): bool
    {
        $cliente = $this->getClienteActual();

        return $cliente && auth()->user()->can('crm.gestionar') && app(WhatsAppCloud::class)->activo()
            && filled($cliente->numeroWhatsapp()) && Notificador::ventanaAbierta($cliente);
    }

    public function abrir(int $cliente): void
    {
        $this->cliente = $cliente;
        $this->texto = '';
    }

    public function enviar(): void
    {
        $this->validate(['texto' => ['required', 'string', 'max:4000']]);

        if (! $this->puedeResponder()) {
            Notification::make()->title('No se puede enviar texto libre')
                ->body('Pasaron más de 24 h desde el último mensaje del cliente: WhatsApp solo permite plantillas aprobadas.')->warning()->send();

            return;
        }

        $cliente = $this->getClienteActual();
        $orden = $cliente->ordenes()->whereNull('cerrada_at')->latest('id')->first();
        app(Notificador::class)->responder($cliente, trim($this->texto), auth()->user(), $orden);
        $this->texto = '';
    }

    public function urlCliente(): ?string
    {
        return $this->cliente ? ClienteResource::getUrl('edit', ['record' => $this->cliente]) : null;
    }
}

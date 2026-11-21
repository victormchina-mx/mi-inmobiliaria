# CLAUDE.md · TARZO 2.0

CRM para taller automotriz (mecánica, hojalatería y pintura). Reemplaza al sistema anterior en
`taller.vmch.net`. Plan completo: "Plan Maestro TARZO 2.0" (6 módulos, 22 semanas).

## Comandos

```bash
composer install && cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate --seed
php artisan tarzo:crear-usuario correo@dominio.com --rol=dueno   # primer usuario
php artisan serve                                               # http://localhost:8000/panel
php artisan test                                                # pruebas (SQLite en memoria)
php artisan test --filter=NombreDeLaPrueba
vendor/bin/pint                                                 # estilo de código
php artisan schedule:list                                       # tareas programadas
```

## Stack

Laravel 13 · PHP 8.3 · Filament 5 (panel en `/panel`) · Livewire 4 · spatie/laravel-permission ·
spatie/laravel-activitylog · MariaDB/MySQL en producción, SQLite en local y pruebas.

## Infraestructura: compartido hoy, VPS después

Arranca en hosting compartido de Hostinger y migra a VPS solo con variables de entorno
(`config/tarzo.php`, `deploy/README.md`). **Todo código nuevo debe respetar estas reglas:**

1. Archivos (fotos, firmas, PDFs) con `Storage::disk(config('tarzo.media.disco'))`. Nunca `public_path()`.
2. Lo lento va a la cola con `tries`/`backoff` y debe terminar en < 50 s por trabajo (el cron lo procesa).
3. Nada exclusivo de Redis sin alternativa `database`.
4. Tiempo real: si `config('tarzo.tiempo_real.modo') === 'polling'` usar `wire:poll`; si es `reverb`, eventos broadcast.
5. Migraciones compatibles con SQLite y MariaDB. Probar con `DB_CONNECTION=mariadb php artisan migrate`.
6. No instalar Horizon ni Reverb todavía: requieren Redis y `pcntl`, que el hosting compartido no tiene.

## Convenciones del dominio

- Tablas y modelos en español; los modelos declaran `$table` explícito (`OrdenServicio` → `ordenes_servicio`).
- Enums en `app/Enums` implementan `HasLabel` de Filament para mostrarse traducidos.
- Lógica de negocio en `app/Actions/{Dominio}` como clases invocables (`AbrirOrden`, `CambiarEtapa`).
- Permisos: `{modulo}.ver` y `{modulo}.gestionar`, definidos en `app/Support/MatrizPermisos.php`.
  Tras cambiar la matriz, correr `php artisan db:seed --class=RolesYPermisosSeeder` (es idempotente).
  El rol `dueno` tiene todo vía `Gate::before`.
- Los Resources de Filament usan el trait `App\Filament\Concerns\ControlaPermisos` y `protected static string $modulo`.
- Etiquetas de interfaz en español, mayúscula solo al inicio (`$hasTitleCaseModelLabel = false`).
- Folios con `App\Support\GeneradorFolio::siguiente('OS')` → `OS-2027-000123`.
- Validación mexicana: `App\Rules\RfcValido`, `App\Rules\VinValido`; catálogos SAT en `sat_regimenes_fiscales` y `sat_usos_cfdi`.
- Modelos importantes usan `LogsActivity` (auditoría) y `SoftDeletes`.
- La orden de servicio (`ordenes_servicio`) es el centro: todo lo demás cuelga de ella.
- En closures de Filament el parámetro de la consulta se llama `$query` (Filament inyecta por nombre; `$q` recibe una consulta vacía).
- Los RelationManagers que deben poder editarse desde una página de consulta declaran `isReadOnly(): false`.
- Archivos de clientes: disco privado `media` (ruta `/archivos`, URL firmada temporal). Usar `App\Support\Media`.
- Enlaces públicos para el cliente: firma relativa (`absolute: false` + middleware `signed:relative`).
- Números y moneda: formato es_MX y MXN configurado globalmente en `AppServiceProvider`.
- Todo cálculo de dinero pasa por `App\Services\Cotizacion\Cotizador` (vista previa y guardado usan el mismo código).
  Si cambias una regla, actualiza `tests/Unit/CotizadorTest.php`, que tiene los casos calculados a mano.
- Las notificaciones de Filament (`sendToDatabase`) van por la cola: en hosting compartido llegan con el cron (≤ 1 min).
- Montos de ejemplo (tarifas, costos por m², cabina) viven en la tabla `parametros`; el seeder no los sobrescribe.
- Los seeders de catálogos usan `firstOrCreate`: nunca deben pisar lo que el dueño configuró en producción.
- Mover órdenes entre etapas siempre con `CambiarEtapa` (aplica reglas y deja historial). Los procesos que ya
  validaron sus permisos (calidad, entrega) pasan `interno: true`.
- En vistas que renderiza Livewire (páginas y componentes de Filament) NO usar `@php(...)` en línea ni `@if`
  de varias líneas dentro de atributos HTML: Livewire 4 los compila mal. Usar `@php … @endphp` y expresiones `{{ }}`.
- Modelos con relación `orden()` no pueden tener una columna llamada `orden` (la columna tapa la relación).
- Existencias y costo promedio SOLO cambian con `App\Services\Inventario\Inventario` (bloquea la fila, deja kárdex
  y avisa al cruzar el mínimo). Toda salida va a una orden o a gasto general con motivo.
- XML de terceros (facturas): parsear con `LIBXML_NONET` y nunca `LIBXML_NOENT`.
- Selects de Filament que validan el valor elegido: usar `->options()` (con `->searchable()`), no solo
  `getSearchResultsUsing`, o la validación rechaza la opción.

## Estructura

```
app/Actions/Ordenes/      AbrirOrden, CambiarEtapa
app/Actions/Recepcion/    FirmarCheckin, EnlaceFirma (firma del inventario)
app/Actions/Cotizacion/   AbrirCotizacion, CrearSuplemento, AgregarPartidas, EnviarCotizacion,
                          ResponderCotizacion, EnlaceAprobacion
app/Services/Cotizacion/  Cotizador (motor de cálculo, reglas documentadas en la clase) y Contexto
app/Actions/Piso/         GenerarTareas, TrasAutorizacion, RelojTarea, RegistrarCalidad, EntregarVehiculo,
                          RegistrarEvidencia
app/Filament/Pages/       Tablero (Kanban), MisTareas (técnico), CargaTrabajo
app/Support/DatosTablero  datos compartidos por el tablero y el modo TV (/panel/tablero-tv)
app/Services/Inventario/  Inventario (único punto que cambia existencias, costo promedio ponderado) y LectorCfdi
app/Actions/Almacen/      CrearRequisiciones, CrearOrdenCompra, CambiarEstadoCompra, RecibirMercancia,
                          VerificarRefacciones (saca la orden de "Espera de refacciones")
app/Jobs/                 GenerarPdfIngreso (PDF + correo al cliente)
app/Services/             DecodificadorVin (NHTSA vPIC)
app/Enums/                Rol, TipoOrden, TipoPersona, CanalPreferido, TipoBahia
app/Filament/Resources/   Órdenes, Check-in, Clientes, Vehículos, Flotillas + catálogos
resources/views/firma/    Página pública donde el cliente firma desde su celular
resources/views/pdf/      PDF del inventario de ingreso
app/Models/               Cliente, Vehiculo, Poliza, OrdenServicio, HistorialEtapa, …
app/Support/              MatrizPermisos, GeneradorFolio, TareasProgramadas, Rfc, Vin
config/tarzo.php          Modo compartido / VPS
deploy/                   Hostinger compartido y VPS
```

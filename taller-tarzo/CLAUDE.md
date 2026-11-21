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

## Estructura

```
app/Actions/Ordenes/      AbrirOrden, CambiarEtapa
app/Enums/                Rol, TipoOrden, TipoPersona, CanalPreferido, TipoBahia
app/Filament/Resources/   Catálogos: Áreas, Bahías, Técnicos, Etapas, Aseguradoras, Usuarios
app/Models/               Cliente, Vehiculo, Poliza, OrdenServicio, HistorialEtapa, …
app/Support/              MatrizPermisos, GeneradorFolio, TareasProgramadas, Rfc, Vin
config/tarzo.php          Modo compartido / VPS
deploy/                   Hostinger compartido y VPS
```

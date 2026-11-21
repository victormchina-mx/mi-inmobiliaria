# Despliegue de TARZO

El sistema arranca en el **hosting compartido de Hostinger** y está preparado para pasar a un
**VPS** cuando haya clientes, cambiando variables de entorno y sin tocar código.

| Tema | Hosting compartido (hoy) | VPS (después) |
|---|---|---|
| Procesar colas (WhatsApp, PDFs, fotos) | Cron cada minuto → `schedule:run` → `queue:work --stop-when-empty` | Supervisor + Horizon, siempre activo |
| Variable clave | `TARZO_COLA_POR_CRON=true` | `TARZO_COLA_POR_CRON=false` |
| Conexión de colas | `QUEUE_CONNECTION=database` | `QUEUE_CONNECTION=redis` |
| Caché y sesiones | `database` | `redis` |
| Tablero en tiempo real | `TARZO_TIEMPO_REAL=polling` (refresco cada 15 s) | `TARZO_TIEMPO_REAL=reverb` (websockets) |
| Fotos y video | `MEDIA_DISK=media` (disco privado del hosting) | `MEDIA_DISK=r2` (Cloudflare R2 privado) |
| Base de datos | MySQL/MariaDB de Hostinger | MySQL 8 / MariaDB en el VPS o administrada |
| Despliegue | `deploy/hostinger-compartido/deploy.sh` por SSH | `deploy/vps/deploy.sh` |

## Reglas para que la migración siga siendo fácil

Todo el código nuevo debe respetar esto (también está en `CLAUDE.md`):

1. **Nada de rutas de disco fijas.** Fotos, firmas y documentos se guardan con
   `Storage::disk(config('tarzo.media.disco'))`, nunca con `public_path()` ni `move()`.
2. **Todo lo lento va a la cola** (envío de WhatsApp/correo, timbrado, PDFs, compresión de fotos),
   con `tries` y `backoff`. Debe terminar en menos de 50 s por trabajo para caber en el cron.
3. **Nada de `Cache::lock` con Redis ni funciones exclusivas de Redis** sin alternativa para `database`.
4. **El tiempo real se consulta por configuración:** si `config('tarzo.tiempo_real.modo') === 'polling'`
   el componente usa `wire:poll`, si es `reverb` escucha el evento por websocket.
5. **Las migraciones deben funcionar en SQLite (pruebas) y MariaDB/MySQL (producción).**

## Carpetas

- `hostinger-compartido/` — instalación inicial, actualización, cron y variables de producción.
- `vps/` — guía de migración, Nginx, Supervisor (Horizon y Reverb) y variables para el VPS.

# Migración de Hostinger compartido a VPS

Objetivo: mover TARZO a un VPS (p. ej. Hostinger KVM 2: 2 vCPU, 8 GB) sin cambiar código.
Tiempo de corte estimado: 1–2 horas fuera de horario (viernes por la tarde).

## Cuándo migrar

Cualquiera de estas señales indica que ya conviene:

- Se activa WhatsApp y los avisos tardan más de 1–2 minutos en salir (el cron solo procesa cada minuto).
- Se quiere el tablero en tiempo real con websockets (Reverb) en lugar de refresco cada 15 s.
- Las fotos y videos ocupan más del 50% del disco del plan compartido.
- Hay más de un taller o más de ~15 usuarios simultáneos.

## Antes del día del corte

1. Contratar el VPS con Ubuntu 24.04 LTS y apuntar un subdominio de prueba (p. ej. `nuevo.vmch.net`).
2. Instalar en el VPS: Nginx, PHP 8.3-FPM (con `redis`, `pcntl`, `intl`, `gd`, `zip`, `bcmath`, `mysql`),
   MariaDB 10.11+ o MySQL 8, Redis, Supervisor, Composer, Certbot.
3. Instalar los paquetes que solo tienen sentido en VPS (requieren Redis y `pcntl`):

   ```bash
   composer require laravel/horizon laravel/reverb
   php artisan horizon:install
   php artisan reverb:install
   ```

   Hacer este cambio en una rama, probarlo en staging y publicarlo **antes** del corte.
4. Crear el bucket en Cloudflare R2 y copiar las fotos actuales:

   ```bash
   rclone copy ~/domains/vmch.net/tarzo-repo/taller-tarzo/storage/app/public r2:tarzo-media
   ```

5. Copiar `nginx/tarzo.conf`, `supervisor/*.conf` y `env.produccion.example` al servidor y ajustarlos.
6. Hacer un ensayo completo en `nuevo.vmch.net` con una copia de la base de datos.

## Día del corte

```bash
# 1. En Hostinger: mantenimiento y respaldo final
php artisan down
mysqldump -u USUARIO -p BASE > tarzo-$(date +%F).sql

# 2. En el VPS: restaurar e instalar
mysql -u tarzo -p tarzo < tarzo-AAAA-MM-DD.sql
bash deploy/vps/deploy.sh

# 3. Sincronizar las fotos subidas desde el ensayo
rclone sync storage/app/public r2:tarzo-media   # (ejecutar en Hostinger)
```

4. Cambiar el DNS del subdominio de producción al IP del VPS (TTL bajo desde un día antes).
5. Emitir el certificado: `certbot --nginx -d tarzo.vmch.net`.
6. Verificar: login, `/up`, `php artisan horizon:status`, un aviso de prueba, el tablero en tiempo real.
7. En Hostinger: quitar el cron de TARZO y dejar el sitio en mantenimiento 30 días antes de borrarlo.

## Variables que cambian

| Variable | Compartido | VPS |
|---|---|---|
| `TARZO_HOSTING` | `compartido` | `vps` |
| `TARZO_COLA_POR_CRON` | `true` | `false` |
| `QUEUE_CONNECTION` | `database` | `redis` |
| `CACHE_STORE` / `SESSION_DRIVER` | `database` | `redis` |
| `TARZO_TIEMPO_REAL` | `polling` | `reverb` |
| `BROADCAST_CONNECTION` | `log` | `reverb` |
| `MEDIA_DISK` | `public` | `r2` |

## Regreso (si algo falla)

El sistema en Hostinger queda intacto en modo mantenimiento. Para regresar: `php artisan up` en Hostinger,
volver el DNS al IP anterior y reactivar el cron. Las órdenes capturadas en el VPS durante el corte
se exportan a mano; por eso el corte se hace fuera de horario.

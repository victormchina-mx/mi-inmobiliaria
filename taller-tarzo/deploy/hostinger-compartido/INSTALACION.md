# Instalación en Hostinger (hosting compartido)

Tiempo estimado: 30–45 minutos. Se instala en un **subdominio nuevo** (ejemplo: `tarzo.vmch.net`)
para no afectar al sistema actual en `taller.vmch.net` mientras se construye el nuevo.

## 1. Preparar hPanel

1. **PHP 8.3 o superior:** hPanel → Avanzado → Configuración de PHP → versión 8.3 (o 8.4).
   Extensiones necesarias (vienen activas por defecto): `pdo_mysql`, `mbstring`, `intl`, `gd`,
   `zip`, `fileinfo`, `bcmath`, `exif`, `sodium`.
2. **Base de datos:** hPanel → Bases de datos → MySQL → crear base, usuario y contraseña.
   Anota los tres datos; el host es `localhost`.
3. **SSH:** hPanel → Avanzado → Acceso SSH → activar. Anota usuario, host y puerto (suele ser 65002).
4. **Subdominio:** hPanel → Dominios → Subdominios → crear `tarzo` sobre `vmch.net`.
   Hostinger crea la carpeta `~/domains/vmch.net/public_html/tarzo`.
5. **SSL:** hPanel → Seguridad → SSL → instalar el certificado gratuito para el subdominio.

## 2. Subir el código (por SSH)

```bash
ssh -p 65002 USUARIO@IP_DEL_SERVIDOR
```

```bash
cd ~/domains/vmch.net
git clone https://github.com/victormchina-mx/REPOSITORIO.git tarzo-repo
```

La aplicación queda **fuera** de `public_html`; solo la carpeta `public/` será visible desde internet.
Si el proyecto vive en una subcarpeta del repositorio (como hoy, `taller-tarzo/`), la ruta de la app es
`~/domains/vmch.net/tarzo-repo/taller-tarzo`. En lo que sigue se llama `APP`.

```bash
cd ~/domains/vmch.net/tarzo-repo/taller-tarzo
composer install --no-dev --optimize-autoloader --no-interaction
```

(Si `composer` no existe, usa `composer2` o `php ~/composer.phar`.)

## 3. Apuntar el subdominio a `public/`

Opción A (recomendada): reemplazar la carpeta del subdominio por un enlace simbólico.

```bash
rm -rf ~/domains/vmch.net/public_html/tarzo
ln -s ~/domains/vmch.net/tarzo-repo/taller-tarzo/public ~/domains/vmch.net/public_html/tarzo
```

Opción B (si Hostinger no respeta el enlace): copia `deploy/hostinger-compartido/htaccess-raiz.txt`
como `.htaccess` en la carpeta del subdominio y coloca ahí la aplicación completa. Es menos segura;
úsala solo si la opción A no funciona.

## 4. Configurar el entorno

```bash
cp deploy/hostinger-compartido/env.produccion.example .env
nano .env        # base de datos, APP_URL, correo
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan optimize
php artisan filament:optimize
```

Crear el primer usuario (pide la contraseña sin mostrarla):

```bash
php artisan tarzo:crear-usuario tu-correo@dominio.com --nombre="Victor China" --rol=dueno
```

## 5. Cron (obligatorio)

hPanel → Avanzado → Trabajos Cron → **Personalizado**, cada minuto (`* * * * *`):

```
cd /home/USUARIO/domains/vmch.net/tarzo-repo/taller-tarzo && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Comprueba la ruta de PHP con `which php` por SSH; en algunos planes es `/opt/alt/php83/usr/bin/php`.
Este cron procesa la cola (avisos, PDFs, fotos) y las tareas programadas.

## 6. Verificar

- `https://tarzo.vmch.net/up` responde 200.
- `https://tarzo.vmch.net/panel` muestra el login.
- Entra con el usuario creado; el sistema pedirá configurar la verificación en dos pasos (dueño y contabilidad).
- `php artisan schedule:list` muestra la tarea `queue:work` cada minuto.

## Actualizaciones posteriores

```bash
cd ~/domains/vmch.net/tarzo-repo/taller-tarzo && bash deploy/hostinger-compartido/deploy.sh
```

## Respaldos

- Hostinger hace respaldos automáticos (diarios o semanales según el plan); revisa hPanel → Archivos → Respaldos.
- Antes de cada actualización importante: hPanel → Bases de datos → phpMyAdmin → Exportar.

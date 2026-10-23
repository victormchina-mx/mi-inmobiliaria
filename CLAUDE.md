# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repositorio

- **GitHub:** https://github.com/victormchina-mx/mi-inmobiliaria
- **Producción:** https://inmobiliaria.vmch.net/

## Comandos esenciales

```bash
# Servidor de desarrollo
php spark serve

# Migraciones
php spark migrate
php spark migrate:rollback

# Ejecutar pruebas
composer test
# o directamente:
php vendor/bin/phpunit

# Ejecutar una sola prueba
php vendor/bin/phpunit --filter NombreDeLaPrueba

# Crear un nuevo controlador/modelo/migración
php spark make:controller NombreControlador
php spark make:model NombreModelo
php spark make:migration NombreMigracion
```

La URL de desarrollo es `https://inmobiliaria.vmch.net/` (según `.env`). En local suele ser `http://localhost:8080`.

## Arquitectura general

Aplicación de marketplace inmobiliario con tres niveles de usuario y un panel de administración independiente.

### Autenticación y sesiones

Hay **dos sistemas de auth separados**, cada uno con su propio filtro y sesión:

| Sistema | Filtro | Variables de sesión | Ruta de login |
|---------|--------|---------------------|---------------|
| Usuario/Agente/Builder | `user-auth` (`UserAuthFilter`) | `uid`, `uemail`, `uname`, `utype` | `/login` |
| Administrador | `admin-auth` (`AdminAuthFilter`) | `auser`, `aid` | `/admin/login` |

Los filtros están en `app/Filters/`. Las contraseñas se hashean con BCRYPT desde los métodos `hashPassword()` en cada modelo, disparados como callback en insert/update.

### Modelos y sus relaciones

- **PropertyModel** (`tabla: property`, PK: `pid`) — es el modelo central. Métodos clave: `withOwner()` (JOIN con `user`), `featured()`, `byUser($uid)`, `byState($state)`, `applyFilters($filters)`.
- **UserModel** (`tabla: user`, PK: `uid`) — tipos: `user`, `agent`, `builder`. Métodos: `users()`, `agents()`, `builders()`.
- **CityModel** (`tabla: city`, PK: `cid`) — `withState()` hace JOIN con `state`.
- **FeedbackModel** (`tabla: feedback`, PK: `fid`) — `status=0` es pendiente, `status=1` es aprobado. `active()` devuelve solo los aprobados con JOIN a `user`.
- **AdminModel** (`tabla: admin`, PK: `aid`) — gestiona credenciales de administradores.

### Estructura de controladores

```
app/Controllers/
├── Home.php, Auth.php, Property.php, Agent.php, About.php, Contact.php, Calculator.php
├── User/
│   ├── Profile.php      # /profile, /profile/feedback
│   └── MyProperty.php   # /my-properties (CRUD completo)
└── Admin/
    ├── Auth.php, Dashboard.php, Profile.php
    ├── Property.php, User.php, State.php, City.php
    ├── Contact.php, Feedback.php, About.php, AdminManager.php
```

Los controladores de admin están en el namespace `App\Controllers\Admin` y sus rutas tienen el prefijo `/admin/`.

### Subida de archivos

Las imágenes se suben a directorios dentro de `public/uploads/`:

- `public/uploads/user/` — fotos de perfil de usuarios
- `public/uploads/property/` — hasta 4 imágenes de propiedad + 3 imágenes de planos (`mapimage`, `topmapimage`, `groundmapimage`)
- `public/uploads/about/` — imágenes de secciones "Sobre nosotros"

El método `uploadPropertyImages()` en `User\MyProperty` y `Admin\Property` gestiona los 8 campos de imagen con soporte para JPEG, PNG, GIF y WebP.

### Vistas y layouts

Dos layouts base:
- `app/Views/layouts/main.php` — frontend público (Bootstrap 4, jQuery, Owl Carousel)
- `app/Views/layouts/admin.php` — panel de administración con sidebar

Las vistas se cargan con `return view('layouts/main', ['content' => view('nombre/vista', $data)])`.

### Base de datos

Las 8 tablas principales (`property`, `user`, `admin`, `state`, `city`, `contact`, `feedback`, `about`) **no se crean mediante migraciones** — existen directamente en la base de datos. La única migración presente (`CrearTablaPrueba`) es de prueba. Para trabajar en local se necesita importar el esquema SQL manualmente.

La configuración de BD está en `app/Config/Database.php` y se sobreescribe con las variables del `.env`.

## Configuración del entorno

Copiar `env` a `.env` y ajustar:

```ini
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080'

database.default.hostname = localhost
database.default.database = nombre_bd
database.default.username = usuario
database.default.password = contraseña
```

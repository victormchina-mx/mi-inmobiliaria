<?php

/*
|--------------------------------------------------------------------------
| Configuración de infraestructura de TARZO
|--------------------------------------------------------------------------
|
| El sistema arranca en hosting compartido (Hostinger) y está preparado para
| migrar a un VPS cambiando solo variables de entorno. Ver deploy/README.md.
|
|  Servidor compartido                 VPS
|  ---------------------------------   ---------------------------------
|  QUEUE_CONNECTION=database           QUEUE_CONNECTION=redis (Horizon)
|  TARZO_COLA_POR_CRON=true            TARZO_COLA_POR_CRON=false
|  CACHE_STORE=database                CACHE_STORE=redis
|  SESSION_DRIVER=database             SESSION_DRIVER=redis
|  TARZO_TIEMPO_REAL=polling           TARZO_TIEMPO_REAL=reverb
|  MEDIA_DISK=public                   MEDIA_DISK=r2
|
*/

return [

    'nombre_comercial' => env('TARZO_NOMBRE', 'TARZO'),

    // 'compartido' o 'vps'. Solo informativo para diagnósticos y el panel.
    'hosting' => env('TARZO_HOSTING', 'compartido'),

    'cola' => [
        // En hosting compartido no hay Supervisor: el cron ejecuta schedule:run cada
        // minuto y el scheduler procesa la cola hasta vaciarla (máx. ~50 s).
        'por_cron' => (bool) env('TARZO_COLA_POR_CRON', true),
        'max_segundos' => (int) env('TARZO_COLA_MAX_SEGUNDOS', 50),
        'colas' => env('TARZO_COLAS', 'alta,default,baja'),
    ],

    'tiempo_real' => [
        // 'polling' (Livewire wire:poll, funciona en compartido) o 'reverb' (websockets en VPS).
        'modo' => env('TARZO_TIEMPO_REAL', 'polling'),
        'intervalo_polling' => env('TARZO_INTERVALO_POLLING', '15s'),
    ],

    'media' => [
        // Disco para fotos, video, firmas y documentos. 'public' en compartido, 'r2' o 's3' en VPS.
        'disco' => env('MEDIA_DISK', 'public'),
    ],

    'seguridad' => [
        'hsts' => (bool) env('TARZO_HSTS', false),
    ],

];

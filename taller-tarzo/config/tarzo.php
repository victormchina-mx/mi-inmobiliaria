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
|  MEDIA_DISK=media                    MEDIA_DISK=r2
|
*/

return [

    'nombre_comercial' => env('TARZO_NOMBRE', 'TARZO'),

    // Datos del taller que aparecen en el PDF de ingreso, el aviso de privacidad y los correos.
    'taller' => [
        'razon_social' => env('TARZO_RAZON_SOCIAL', env('TARZO_NOMBRE', 'TARZO')),
        'domicilio' => env('TARZO_DOMICILIO', ''),
        'telefono' => env('TARZO_TELEFONO', ''),
        'email' => env('TARZO_EMAIL', ''),
        'email_privacidad' => env('TARZO_EMAIL_PRIVACIDAD', env('TARZO_EMAIL', '')),
    ],

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
        // Disco privado para fotos, video, firmas y documentos: 'media' en compartido, 'r2' en VPS.
        'disco' => env('MEDIA_DISK', 'media'),
        'max_foto_px' => 1600,       // las fotos se reducen en el navegador antes de subirlas
        'max_video_mb' => 60,
    ],

    // WhatsApp Business Platform (Cloud API de Meta).
    //  'desactivado': los avisos se mandan por correo (mientras Meta aprueba el número y las plantillas).
    //  'cloud': envío real por la API de Meta.   'log': simula el envío y lo escribe en el log (pruebas).
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'desactivado'),
        'token' => env('WHATSAPP_TOKEN'),                    // token permanente del usuario del sistema
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),          // para validar la firma del webhook
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),      // el que se captura al configurar el webhook en Meta
        'version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'idioma' => env('WHATSAPP_IDIOMA', 'es_MX'),
    ],

    // Postventa
    'url_resena' => env('TARZO_URL_RESENA', ''), // enlace "Escríbenos una reseña" de Google Business

    'seguridad' => [
        'hsts' => (bool) env('TARZO_HSTS', false),
    ],

];

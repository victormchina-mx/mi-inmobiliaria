<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Datos de contacto de la inmobiliaria que se muestran en las páginas
 * Contacto y Nosotros (botones de llamar, WhatsApp y correo).
 *
 * Nota: la barra superior y el pie (layouts/main.php) y el JSON-LD del
 * inicio todavía los tienen escritos directamente; si cambian, cambiarlos
 * también ahí.
 */
class Site extends BaseConfig
{
    public string $phone = '55 7606 2384';
    public string $email = 'inmobiliaria.info@vmch.net';

    /** Mensaje con el que se abre WhatsApp desde Contacto y Nosotros. */
    public string $whatsappMessage = 'Hola, quiero información sobre sus propiedades.';
}

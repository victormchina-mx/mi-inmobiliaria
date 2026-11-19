<?php

namespace App\Libraries;

/**
 * Foto de fondo de la portada del inicio ("Déjanos guiarte a tu hogar").
 * Se cambia en Admin → Fondos → Portada del inicio. Lógica: Backdrop.
 */
class HomeHero extends Backdrop
{
    public const DEFAULT    = 'images/banner/rshmpg.webp';
    protected const DIR     = 'home';
    protected const CONFIG  = 'home-hero.json';
    protected const PREFIX  = 'portada';
}

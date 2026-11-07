<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('asset_url')) {
    /**
     * URL de un archivo de public/ con su versión (?v=fecha de modificación).
     *
     * El servidor indica a los navegadores que guarden CSS y JS 7 días; sin
     * versión, quien ya visitó el sitio seguía viendo los estilos viejos
     * después de un cambio. Con ?v=..., cada vez que el archivo cambia la URL
     * es otra y el navegador lo descarga de nuevo.
     */
    function asset_url(string $path): string
    {
        $path = ltrim($path, '/');
        $file = FCPATH . $path;
        $url  = base_url($path);

        return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
    }
}

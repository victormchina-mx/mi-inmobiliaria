<?php

namespace App\Libraries;

/**
 * Etiquetas en español para los campos de los formularios y la API.
 *
 * Sin etiqueta, CodeIgniter usa el nombre interno del campo en los
 * mensajes ("El campo uname es obligatorio."). apply() agrega la etiqueta
 * a cada regla que no la tenga, sin cambiar las reglas.
 *
 * Se usa en BaseController::validate()/validateData() y en el
 * initialize() de los modelos.
 */
class FieldLabels
{
    public const LABELS = [
        // Usuarios y acceso
        'uname'  => 'nombre',
        'name'   => 'nombre',
        'email'  => 'correo electrónico',
        'uemail' => 'correo electrónico',
        'aemail' => 'correo electrónico',
        'pass'   => 'contraseña',
        'upass'  => 'contraseña',
        'apass'  => 'contraseña',
        'phone'  => 'teléfono',
        'uphone' => 'teléfono',
        'aphone' => 'teléfono',
        'utype'  => 'tipo de usuario',
        'auser'  => 'usuario',
        'adob'   => 'fecha de nacimiento',
        'uid'    => 'usuario',

        // Propiedades
        'title'  => 'título',
        'ptype'  => 'tipo de propiedad',
        'type'   => 'tipo de propiedad',
        'stype'  => 'tipo de operación',
        'price'  => 'precio',
        'status' => 'estado de la propiedad',

        // Catálogos
        'sname' => 'nombre del estado',
        'cname' => 'nombre de la ciudad',
        'sid'   => 'estado',

        // Contacto, opiniones y "Nosotros"
        'subject'      => 'asunto',
        'message'      => 'mensaje',
        'content'      => 'contenido',
        'fdescription' => 'opinión',
    ];

    /**
     * @param array|string $rules Reglas de validación ('campo' => 'reglas' o
     *                            'campo' => ['rules' => ..., 'label' => ...]).
     *                            Un string (nombre de un grupo) se devuelve igual.
     *
     * @return array|string
     */
    public static function apply($rules)
    {
        if (! is_array($rules)) {
            return $rules;
        }

        foreach ($rules as $field => $rule) {
            if (! is_string($field) || ! isset(self::LABELS[$field])) {
                continue;
            }

            if (is_string($rule) || (is_array($rule) && ! isset($rule['rules']))) {
                // 'campo' => 'required|...' o 'campo' => ['required', ...]
                $rules[$field] = ['label' => self::LABELS[$field], 'rules' => $rule];
            } elseif (is_array($rule) && empty($rule['label'])) {
                $rules[$field]['label'] = self::LABELS[$field];
            }
        }

        return $rules;
    }
}

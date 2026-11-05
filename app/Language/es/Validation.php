<?php

/**
 * Mensajes de validación en español.
 * Mismas claves que system/Language/en/Validation.php de CodeIgniter 4.7.3.
 * {field} es la etiqueta del campo (ver App\Libraries\FieldLabels).
 */
return [
    // Mensajes del núcleo
    'noRuleSets'      => 'No se especificaron conjuntos de reglas en la configuración de validación.',
    'ruleNotFound'    => '"{0}" no es una regla válida.',
    'groupNotFound'   => '"{0}" no es un grupo de reglas de validación.',
    'groupNotArray'   => 'El grupo de reglas "{0}" debe ser un arreglo.',
    'invalidTemplate' => '"{0}" no es una plantilla de validación válida.',

    // Mensajes de las reglas
    'alpha'                 => 'El campo {field} solo puede contener letras.',
    'alpha_dash'            => 'El campo {field} solo puede contener letras, números, guiones bajos y guiones.',
    'alpha_numeric'         => 'El campo {field} solo puede contener letras y números.',
    'alpha_numeric_punct'   => 'El campo {field} solo puede contener letras, números, espacios y los caracteres ~ ! # $ % & * - _ + = | : .',
    'alpha_numeric_space'   => 'El campo {field} solo puede contener letras, números y espacios.',
    'alpha_space'           => 'El campo {field} solo puede contener letras y espacios.',
    'decimal'               => 'El campo {field} debe ser un número.',
    'differs'               => 'El campo {field} debe ser distinto del campo {param}.',
    'equals'                => 'El campo {field} debe ser exactamente: {param}.',
    'exact_length'          => 'El campo {field} debe tener exactamente {param} caracteres.',
    'field_exists'          => 'El campo {field} debe existir.',
    'greater_than'          => 'El campo {field} debe ser un número mayor que {param}.',
    'greater_than_equal_to' => 'El campo {field} debe ser un número mayor o igual que {param}.',
    'hex'                   => 'El campo {field} solo puede contener caracteres hexadecimales.',
    'in_list'               => 'El campo {field} debe ser uno de: {param}.',
    'integer'               => 'El campo {field} debe ser un número entero.',
    'is_natural'            => 'El campo {field} solo puede contener dígitos.',
    'is_natural_no_zero'    => 'El campo {field} solo puede contener dígitos y debe ser mayor que cero.',
    'is_not_unique'         => 'El campo {field} debe contener un valor que ya exista.',
    'is_unique'             => 'El {field} ya está registrado.',
    'less_than'             => 'El campo {field} debe ser un número menor que {param}.',
    'less_than_equal_to'    => 'El campo {field} debe ser un número menor o igual que {param}.',
    'matches'               => 'El campo {field} no coincide con el campo {param}.',
    'max_length'            => 'El campo {field} no puede tener más de {param} caracteres.',
    'min_length'            => 'El campo {field} debe tener al menos {param} caracteres.',
    'not_equals'            => 'El campo {field} no puede ser: {param}.',
    'not_in_list'           => 'El campo {field} no puede ser ninguno de: {param}.',
    'numeric'               => 'El campo {field} solo puede contener números.',
    'regex_match'           => 'El campo {field} no tiene el formato correcto.',
    'required'              => 'El campo {field} es obligatorio.',
    'required_with'         => 'El campo {field} es obligatorio cuando se indica {param}.',
    'required_without'      => 'El campo {field} es obligatorio cuando no se indica {param}.',
    'string'                => 'El campo {field} debe ser un texto válido.',
    'timezone'              => 'El campo {field} debe ser una zona horaria válida.',
    'valid_base64'          => 'El campo {field} debe ser un texto base64 válido.',
    'valid_email'           => 'El campo {field} debe ser una dirección de correo válida.',
    'valid_emails'          => 'El campo {field} debe contener solo direcciones de correo válidas.',
    'valid_ip'              => 'El campo {field} debe ser una dirección IP válida.',
    'valid_url'             => 'El campo {field} debe ser una URL válida.',
    'valid_url_strict'      => 'El campo {field} debe ser una URL válida.',
    'valid_date'            => 'El campo {field} debe ser una fecha válida.',
    'valid_json'            => 'El campo {field} debe ser un JSON válido.',

    // Tarjetas de crédito
    'valid_cc_number' => 'El campo {field} no parece ser un número de tarjeta válido.',

    // Archivos
    'uploaded' => 'El archivo {field} no se subió correctamente.',
    'max_size' => 'El archivo {field} es demasiado grande.',
    'is_image' => 'El archivo {field} no es una imagen válida.',
    'mime_in'  => 'El archivo {field} no tiene un formato permitido.',
    'ext_in'   => 'El archivo {field} no tiene una extensión permitida.',
    'max_dims' => 'La imagen {field} es demasiado ancha o alta, o no es una imagen.',
    'min_dims' => 'La imagen {field} no es lo bastante ancha o alta, o no es una imagen.',
];

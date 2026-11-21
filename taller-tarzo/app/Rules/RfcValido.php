<?php

namespace App\Rules;

use App\Support\Rfc;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RfcValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Rfc::esValido($value)) {
            $fail('El RFC no tiene un formato válido (12 caracteres para persona moral, 13 para física).');
        }
    }
}

<?php

namespace App\Rules;

use App\Support\Vin;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VinValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Vin::formatoValido($value)) {
            $fail('El VIN debe tener 17 caracteres y no puede contener las letras I, O ni Q.');
        }
    }
}

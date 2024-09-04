<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumberFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! VatValidator::validateFormat($value)) {
            $fail(__('laravelVatEuValidator::validation.vat_number_format', ['attribute' => $attribute]));
        }
    }
}

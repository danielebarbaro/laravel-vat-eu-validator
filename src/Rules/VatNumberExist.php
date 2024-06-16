<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Closure;
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumberExist implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! VatValidator::validateExistence($value)) {
            $fail(__('The :attribute must be write in a valid number format {country_name}{vat_number}.'));
        }
    }
}

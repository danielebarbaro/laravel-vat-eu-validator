<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumber implements ValidationRule
{
    public function isValid(mixed $value): bool
    {
        return VatValidator::validate($value);
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! $this->isValid($value)) {
            $fail(__('laravelVatEuValidator::validation.vat_number', ['attribute' => $attribute]));
        }
    }
}

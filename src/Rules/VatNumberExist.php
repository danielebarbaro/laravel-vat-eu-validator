<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Closure;
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumberExist implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isValid($value)) {
            $fail(__('laravelVatEuValidator::validation.vat_number_exist', ['attribute' => $attribute]));
        }
    }

    private function isValid(mixed $value): bool
    {
        return VatValidator::validateExistence($value);
    }
}

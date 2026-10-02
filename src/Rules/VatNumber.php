<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumber implements ValidationRule
{
    use HandlesViesFailures;

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $message = __('laravelVatEuValidator::validation.vat_number', ['attribute' => $attribute]);

        if (! is_string($value)) {
            $fail($message);

            return;
        }

        try {
            $valid = VatValidator::validate($value);
        } catch (ViesException $viesException) {
            $this->handleViesFailure($viesException, $attribute, $fail);

            return;
        }

        if (! $valid) {
            $fail($message);
        }
    }
}

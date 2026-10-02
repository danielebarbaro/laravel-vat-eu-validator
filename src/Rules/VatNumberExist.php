<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Closure;
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Illuminate\Contracts\Validation\ValidationRule;

class VatNumberExist implements ValidationRule
{
    use HandlesViesFailures;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = __('laravelVatEuValidator::validation.vat_number_exist', ['attribute' => $attribute]);

        if (! is_string($value)) {
            $fail($message);

            return;
        }

        try {
            $exists = VatValidator::validateExistence($value);
        } catch (ViesException $viesException) {
            $this->handleViesFailure($viesException, $attribute, $fail);

            return;
        }

        if (! $exists) {
            $fail($message);
        }
    }
}

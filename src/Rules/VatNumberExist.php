<?php

namespace Danielebarbaro\LaravelVatEuValidator\Rules;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Illuminate\Contracts\Validation\Rule;

class VatNumberExist implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @return bool
     * @throws \Danielebarbaro\LaravelVatEuValidator\Vies\ViesException
     */
    public function passes($attribute, $value)
    {
        return VatValidator::validateExistence($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be write in a valid number format {country_name}{vat_number}.';
    }
}

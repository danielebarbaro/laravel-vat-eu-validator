<?php

namespace Danielebarbaro\LaravelVatEuValidator\Facades;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Illuminate\Support\Facades\Facade;

/**
 * @method bool validateFormat(string $vatNumber)
 * @method bool validateExistence(string $vatNumber)
 * @method bool validate(string $vatNumber)
 * @method static int luhnCheck(string $vatNumber)
 * @method static string countryIsSupported(string $country)
 */
class VatValidatorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return VatValidator::class;
    }
}

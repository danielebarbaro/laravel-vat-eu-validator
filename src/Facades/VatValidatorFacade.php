<?php

namespace Danielebarbaro\LaravelVatEuValidator\Facades;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Illuminate\Support\Facades\Facade;

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

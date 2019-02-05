<?php

namespace Danielebarbaro\LaravelVatEuValidator\Facades;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Danielebarbaro\LaravelVatEuValidator\Skeleton\SkeletonClass
 */
class VatValidatorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return VatValidator::class;
    }
}

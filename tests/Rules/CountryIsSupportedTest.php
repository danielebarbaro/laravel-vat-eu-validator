<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Orchestra\Testbench\TestCase;

class CountryIsSupportedTest extends TestCase
{
    public function testCountryIsSupported()
    {
        self::assertTrue(VatValidator::countryIsSupported('AT'));
    }

    public function testCountryIsNotSupported()
    {
        self::assertFalse(VatValidator::countryIsSupported('US'));
    }
}

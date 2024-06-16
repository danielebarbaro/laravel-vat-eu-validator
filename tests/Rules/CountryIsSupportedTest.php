<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Orchestra\Testbench\TestCase;

class CountryIsSupportedTest extends TestCase
{
    public function testCountryIsSupported(): void
    {
        self::assertTrue(VatValidator::countryIsSupported('AT'));
    }

    public function testCountryIsNotSupported(): void
    {
        self::assertFalse(VatValidator::countryIsSupported('US'));
    }
}

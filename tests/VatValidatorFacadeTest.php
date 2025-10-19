<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade;
use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;

class VatValidatorFacadeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testVatValidatorFacade(): void
    {
        $validator = resolve(VatValidator::class);
        $fake_vat = 'is_a_fake_vat_string';

        self::assertEquals($validator->validate($fake_vat), VatValidatorFacade::validate($fake_vat));
    }
}

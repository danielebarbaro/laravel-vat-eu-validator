<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade;
use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Orchestra\Testbench\TestCase;

class VatValidatorFacadeTest extends TestCase
{
    public function testVatValidatorFacade()
    {
        $validator = new VatValidator();
        $fake_vat = 'is_a_fake_vat_string';

        self::assertEquals($validator->validate($fake_vat), VatValidatorFacade::validate($fake_vat));
    }
}

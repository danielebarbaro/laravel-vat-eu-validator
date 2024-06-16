<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Orchestra\Testbench\TestCase;

class VatNumberExistTest extends TestCase
{
    public function testVatNumberExist(): void
    {
        $rule = new VatNumberExist();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateExistence')
            ->once()
            ->with($fake_vat)
            ->andReturn(true);

        $this->assertNull($rule->validate('vat_number_exist', $fake_vat, function (): never {
            $this->fail('Validation should not fail');
        }));
    }

    public function testVatNumberDoesNotExist(): void
    {
        $rule = new VatNumberExist();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateExistence')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The :attribute must be write in a valid number format {country_name}{vat_number}.');

        $rule->validate('vat_number_exist', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Orchestra\Testbench\TestCase;

class VatNumberTest extends TestCase
{
    public function testVatNumber(): void
    {
        $rule = new VatNumber();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validate')
            ->once()
            ->with($fake_vat)
            ->andReturn(true);

        $this->assertNull($rule->validate('vat_number', $fake_vat, function (): never {
            $this->fail('Validation should not fail');
        }));
    }

    public function testVatNumberNotExist(): void
    {
        $rule = new VatNumber();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validate')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('laravelVatEuValidator::validation.vat_number');

        $rule->validate('vat_number', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }
}

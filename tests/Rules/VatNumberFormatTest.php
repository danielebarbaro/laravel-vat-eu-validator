<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Orchestra\Testbench\TestCase;

class VatNumberFormatTest extends TestCase
{
    public function testVatNumberFormat(): void
    {
        $rule = new VatNumberFormat();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateFormat')
            ->once()
            ->with($fake_vat)
            ->andReturn(true);

        $this->assertNull($rule->validate('vat_number_format', $fake_vat, function (): never {
            $this->fail('Validation should not fail');
        }));
    }

    public function testVatNumberFormatNotExist(): void
    {
        $rule = new VatNumberFormat();
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateFormat')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('laravelVatEuValidator::validation.vat_number_format');

        $rule->validate('vat_number_format', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }
}

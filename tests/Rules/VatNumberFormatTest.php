<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;

class VatNumberFormatTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testVatNumberFormat(): void
    {
        $rule = resolve(VatNumberFormat::class);
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
        $rule = resolve(VatNumberFormat::class);
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateFormat')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('laravelVatEuValidator::validation.vat_number_format', ['attribute' => 'vat_number_format']));

        $rule->validate('vat_number_format', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }
}

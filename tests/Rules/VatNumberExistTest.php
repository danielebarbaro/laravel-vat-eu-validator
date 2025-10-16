<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;

class VatNumberExistTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testVatNumberExist(): void
    {
        $rule = resolve(VatNumberExist::class);
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
        $rule = resolve(VatNumberExist::class);
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validateExistence')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('laravelVatEuValidator::validation.vat_number_exist', ['attribute' => 'vat_number_exist']));

        $rule->validate('vat_number_exist', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }
}

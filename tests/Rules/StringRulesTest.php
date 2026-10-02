<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The string rules registered by the service provider, run through the
 * Laravel validator.
 */
class StringRulesTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function viesRules(): array
    {
        return [
            'vat_number' => ['vat_number', 'validate'],
            'vat_number_exist' => ['vat_number_exist', 'validateExistence'],
        ];
    }

    #[DataProvider('viesRules')]
    public function testViesFailureThrowsByDefault(string $rule, string $method): void
    {
        VatValidator::shouldReceive($method)->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->expectException(ViesException::class);

        Validator::make(['vat' => 'IT00743110157'], ['vat' => $rule])->passes();
    }

    #[DataProvider('viesRules')]
    public function testViesFailureFailsWithTheDedicatedMessageWhenConfigured(string $rule, string $method): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive($method)->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $validator = Validator::make(['vat' => 'IT00743110157'], ['vat' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            [__('laravelVatEuValidator::validation.vies_unavailable')],
            $validator->errors()->get('vat')
        );
    }

    #[DataProvider('viesRules')]
    public function testViesFailurePassesWhenConfigured(string $rule, string $method): void
    {
        config(['vat-validator.on_vies_failure' => 'pass']);
        VatValidator::shouldReceive($method)->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->assertTrue(Validator::make(['vat' => 'IT00743110157'], ['vat' => $rule])->passes());
    }

    #[DataProvider('viesRules')]
    public function testInvalidNumberKeepsTheRuleMessageWhenViesFailuresFail(string $rule, string $method): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive($method)->once()->andReturn(false);

        $validator = Validator::make(['vat' => 'IT00743110158'], ['vat' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            [__("laravelVatEuValidator::validation.{$rule}", ['attribute' => 'vat'])],
            $validator->errors()->get('vat')
        );
    }

    /**
     * @return array<string, array{string, mixed}>
     */
    public static function nonStringValues(): array
    {
        $values = [];

        foreach (['vat_number', 'vat_number_exist', 'vat_number_format'] as $rule) {
            $values["{$rule} null"] = [$rule, null];
            $values["{$rule} array"] = [$rule, ['IT00743110157']];
        }

        return $values;
    }

    #[DataProvider('nonStringValues')]
    public function testNonStringValueFailsValidation(string $rule, mixed $value): void
    {
        $validator = Validator::make(['vat' => $value], ['vat' => $rule]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            [__("laravelVatEuValidator::validation.{$rule}", ['attribute' => 'vat'])],
            $validator->errors()->get('vat')
        );
    }

    public function testNullValueIsSkippedWhenNullable(): void
    {
        $this->assertTrue(Validator::make(['vat' => null], ['vat' => ['nullable', 'vat_number']])->passes());
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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

    public function testViesFailureThrowsByDefault(): void
    {
        VatValidator::shouldReceive('validateExistence')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->expectException(ViesException::class);

        (new VatNumberExist())->validate('vat_number_exist', 'IT00743110157', function (): never {
            $this->fail('Validation should not fail');
        });
    }

    public function testViesFailureFailsWhenConfigured(): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive('validateExistence')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $rule = new VatNumberExist();
        $messages = [];
        $rule->validate('vat_number_exist', 'IT00743110157', static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vies_unavailable')], $messages);
        $this->assertTrue($rule->failedBecauseViesIsUnavailable());
    }

    public function testViesFailurePassesWhenConfigured(): void
    {
        config(['vat-validator.on_vies_failure' => 'pass']);
        VatValidator::shouldReceive('validateExistence')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $rule = new VatNumberExist();
        $rule->validate('vat_number_exist', 'IT00743110157', function (): never {
            $this->fail('Validation should not fail');
        });

        $this->assertFalse($rule->failedBecauseViesIsUnavailable());
    }

    public function testUnknownViesFailureSettingThrows(): void
    {
        config(['vat-validator.on_vies_failure' => 'ignore']);
        VatValidator::shouldReceive('validateExistence')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown vat-validator.on_vies_failure value: ignore');

        (new VatNumberExist())->validate('vat_number_exist', 'IT00743110157', static function (): void {
        });
    }

    public function testInvalidNumberStillFailsWithItsOwnMessageWhenViesFailuresFail(): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive('validateExistence')->once()->andReturn(false);

        $rule = new VatNumberExist();
        $messages = [];
        $rule->validate('vat_number_exist', 'IT00743110158', static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vat_number_exist', ['attribute' => 'vat_number_exist'])], $messages);
        $this->assertFalse($rule->failedBecauseViesIsUnavailable());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function nonStringValues(): array
    {
        return [
            'null' => [null],
            'array' => [['IT00743110157']],
            'integer' => [743110157],
        ];
    }

    #[DataProvider('nonStringValues')]
    public function testNonStringValueFailsWithoutCallingTheValidator(mixed $value): void
    {
        VatValidator::shouldReceive('validateExistence')->never();

        $messages = [];
        (new VatNumberExist())->validate('vat_number_exist', $value, static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vat_number_exist', ['attribute' => 'vat_number_exist'])], $messages);
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class VatNumberTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testVatNumber(): void
    {
        $rule = resolve(VatNumber::class);
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
        $rule = resolve(VatNumber::class);
        $fake_vat = 'is_a_fake_vat_string';

        VatValidator::shouldReceive('validate')
            ->once()
            ->with($fake_vat)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('laravelVatEuValidator::validation.vat_number', ['attribute' => 'vat_number']));

        $rule->validate('vat_number', $fake_vat, static function ($message): never {
            throw new \Exception($message);
        });
    }

    public function testViesFailureThrowsByDefault(): void
    {
        VatValidator::shouldReceive('validate')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->expectException(ViesException::class);

        (new VatNumber())->validate('vat_number', 'IT00743110157', function (): never {
            $this->fail('Validation should not fail');
        });
    }

    public function testViesFailureFailsWhenConfigured(): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive('validate')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $rule = new VatNumber();
        $messages = [];
        $rule->validate('vat_number', 'IT00743110157', static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vies_unavailable')], $messages);
        $this->assertTrue($rule->failedBecauseViesIsUnavailable());
    }

    public function testViesFailurePassesWhenConfigured(): void
    {
        config(['vat-validator.on_vies_failure' => 'pass']);
        VatValidator::shouldReceive('validate')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $rule = new VatNumber();
        $rule->validate('vat_number', 'IT00743110157', function (): never {
            $this->fail('Validation should not fail');
        });

        $this->assertFalse($rule->failedBecauseViesIsUnavailable());
    }

    public function testUnknownViesFailureSettingThrows(): void
    {
        config(['vat-validator.on_vies_failure' => 'ignore']);
        VatValidator::shouldReceive('validate')->once()->andThrow(new ViesException('MS_UNAVAILABLE'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown vat-validator.on_vies_failure value: ignore');

        (new VatNumber())->validate('vat_number', 'IT00743110157', static function (): void {
        });
    }

    public function testInvalidNumberStillFailsWithItsOwnMessageWhenViesFailuresFail(): void
    {
        config(['vat-validator.on_vies_failure' => 'fail']);
        VatValidator::shouldReceive('validate')->once()->andReturn(false);

        $rule = new VatNumber();
        $messages = [];
        $rule->validate('vat_number', 'IT00743110158', static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vat_number', ['attribute' => 'vat_number'])], $messages);
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
        VatValidator::shouldReceive('validate')->never();

        $messages = [];
        (new VatNumber())->validate('vat_number', $value, static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vat_number', ['attribute' => 'vat_number'])], $messages);
    }
}

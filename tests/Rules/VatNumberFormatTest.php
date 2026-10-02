<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
        VatValidator::shouldReceive('validateFormat')->never();

        $messages = [];
        (new VatNumberFormat())->validate('vat_number_format', $value, static function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        $this->assertSame([__('laravelVatEuValidator::validation.vat_number_format', ['attribute' => 'vat_number_format'])], $messages);
    }
}

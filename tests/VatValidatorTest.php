<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class VatValidatorTest extends TestCase
{
    protected VatValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = resolve(VatValidator::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    #[DataProvider('validVatFormatsProvider')]
    /**
     * @dataProvider validVatFormatsProvider
     */
    public function testValidateFormatSuccess(string $vatNumber, ?string $defaultCountry = null): void
    {
        self::assertTrue(
            $this->validator->validateFormat($vatNumber, $defaultCountry),
            "Failed asserting that '{$vatNumber}' (default: {$defaultCountry}) is a valid format."
        );
    }

    #[DataProvider('invalidVatFormatsProvider')]
    /**
     * @dataProvider invalidVatFormatsProvider
     */
    public function testValidateFormatFailure(string $vatNumber, ?string $defaultCountry = null): void
    {
        self::assertFalse(
            $this->validator->validateFormat($vatNumber, $defaultCountry),
            "Failed asserting that '{$vatNumber}' (default: {$defaultCountry}) is an invalid format."
        );
    }

    public static function validVatFormatsProvider(): array
    {
        return [
            // Italy (Luhn + Office code)
            'IT valid standard' => ['IT10648200011'],
            'IT valid with spaces and dashes' => [' IT 106 482 000 11 '],
            'IT valid with default country' => ['10648200011', 'IT'],

            // France (Modulo 97 + Key alphabetic or numeric)
            'FR valid numeric key' => ['FR40303265045'],
            'FR valid alphabetic key (collision test HU)' => ['HU123456789', 'FR'],
            'FR valid without prefix' => ['40303265045', 'FR'],
            'FR valid alphabetic key colliding with HU prefix' => ['HU123456789', 'FR'],

            // Hungary (Checksum pondered + 8 digits)
            'HU valid standard' => ['HU28395515'],
            'HU valid without prefix' => ['28395515', 'HU'],

            // Greece (Conversion ISO GR -> VIES EL)
            'EL valid standard' => ['EL094259216'],
            'GR valid prefix alias' => ['GR094259216'],
            'GR valid with default country' => ['094259216', 'GR'],

            // Spain (Alternances formats)
            'ES valid format 1 (A1234567B)' => ['ESA1234567B'],
            'ES valid format 2 (12345678A)' => ['ES12345678A'],
            'ES valid format 3 (A12345678)' => ['ESA12345678'],

            // Ireland (Formats XI)
            'XI valid standard 9 digits' => ['XI123456789'],
            'XI valid special prefix GD' => ['XIGD123'],

            // Other countries (Base formats)
            'DE valid' => ['DE123456789'],
            'AT valid' => ['ATU12345678'],
            'BE valid' => ['BE0123456789'],
            'DK valid' => ['DK12345678'],
            'NL valid' => ['NL123456789B01'],
        ];
    }

    public static function invalidVatFormatsProvider(): array
    {
        return [
            // Generic cases and character traps
            'Empty string' => [''],
            'Random string' => ['foobar123'],
            'Unsupported country prefix' => ['US123456789'],
            'Unknown default country fallback' => ['12345678', 'XX'],

            // Non-isolated alternation trap (Regex bug that leaks)
            'ES leakage test' => ['ESXX12345678AYY'], 
            'XI leakage test' => ['XIFOO123456789012BAR'],

            // Italy (Specific errors)
            'IT invalid length' => ['IT1234567890'],
            'IT invalid check digit (Luhn)' => ['IT10648200012'],
            'IT all zeros' => ['IT00000000000'],
            'IT invalid office code (000)' => ['IT12345670001'],

            // France (Specific errors)
            'FR invalid modulo 97 key' => ['FR41303265045'],
            'FR invalid character in key (O or I forbidden)' => ['FRIO303265045'],

            // Hungary (Specific errors)
            'HU invalid checksum' => ['HU28395514'],
            'HU wrong length' => ['HU2839551'],
        ];
    }

    public function testCountryIsSupported(): void
    {
        self::assertTrue(VatValidator::countryIsSupported('FR'));
        self::assertTrue(VatValidator::countryIsSupported('IT'));
        self::assertFalse(VatValidator::countryIsSupported('US'));
    }
}
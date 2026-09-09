<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class VatValidatorTest extends TestCase
{
    protected VatValidator $validator;

    protected string $fake_vat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = resolve(VatValidator::class);
        $this->fake_vat = 'IT12345678901';
    }

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testVatValidFormatFail(): void
    {
        self::assertFalse($this->validator->validateFormat($this->fake_vat));
    }

    #[DataProvider('invalidVatFormatsProvider')]
    public function testVatFormatAlternationLeakage(string $vat_number): void
    {
        self::assertFalse($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('validXiVatFormatsProvider')]
    public function testXiVatValidFormat(string $vat_number): void
    {
        self::assertTrue($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('invalidXiVatFormatsProvider')]
    public function testXiVatInvalidFormat(string $vat_number): void
    {
        self::assertFalse($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('validFrVatFormatsProvider')]
    public function testFrVatValidFormat(string $vat_number): void
    {
        self::assertTrue($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('invalidFrVatFormatsProvider')]
    public function testFrVatInvalidFormat(string $vat_number): void
    {
        self::assertFalse($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('validChVatFormatsProvider')]
    public function testChVatValidFormat(string $vat_number): void
    {
        self::assertTrue($this->validator->validateFormat($vat_number));
    }

    #[DataProvider('invalidChVatFormatsProvider')]
    public function testChVatInvalidFormat(string $vat_number): void
    {
        self::assertFalse($this->validator->validateFormat($vat_number));
    }

    /**
     * Numbers whose alternation branches used to match unanchored.
     *
     * @return array<string, array<string>>
     */
    public static function invalidVatFormatsProvider(): array
    {
        return [
            'ES leakage test' => ['ESXX12345678AYY'],
            'IE leakage test' => ['IEXX1234567WAYY'],
            'GB leakage test' => ['GBXX123456789012YY'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function validXiVatFormatsProvider(): array
    {
        return [
            'XI valid standard 9 digits' => ['XI123456789'],
            'XI valid 12 digits' => ['XI123456789012'],
            'XI valid special prefix GD' => ['XIGD123'],
            'XI valid special prefix HA' => ['XIHA123'],
            'XI valid lowercase' => ['xi123456789'],
            'XI valid with surrounding spaces' => [' XI123456789 '],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidXiVatFormatsProvider(): array
    {
        return [
            'XI invalid 8 digits' => ['XI12345678'],
            'XI invalid 10 digits' => ['XI1234567890'],
            'XI invalid 13 digits' => ['XI1234567890123'],
            'XI invalid characters' => ['XIA12345678'],
            'XI invalid GD too short' => ['XIGD12'],
            'XI invalid HA too long' => ['XIHA1234'],
            'XI invalid unknown prefix' => ['XIZZ123'],
            'XI leakage test' => ['XIXX123456789012YY'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function validFrVatFormatsProvider(): array
    {
        return [
            'FR valid numeric key (Modulo 97)' => ['FR40303265045'],
            'FR valid numeric key from the official example' => ['FR83404833048'],
            'FR valid numeric key with a leading zero' => ['FR00100000012'],
            'FR valid SIREN with leading zeros' => ['FR34000123456'],
            'FR valid alphabetic key without forbidden chars' => ['FRHU356000000'],
            'FR valid alphanumeric key' => ['FR2A356000000'],
        ];
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidFrVatFormatsProvider(): array
    {
        return [
            'FR invalid modulo 97 key' => ['FR41303265045'],
            'FR forbidden letter O in first key char' => ['FRO0303265045'],
            'FR forbidden letter I in first key char' => ['FRI0303265045'],
            'FR forbidden letter O in second key char' => ['FR1O303265045'],
            'FR forbidden letter I in second key char' => ['FR1I303265045'],
            'FR SIREN too short' => ['FR4030326504'],
            'FR SIREN too long' => ['FR403032650456'],
        ];
    }

    public static function validChVatFormatsProvider(): array
    {
        return [
            'CH valid 6 digits' => ['CH123456'],
            'CH valid E + 9 digits' => ['CHE123456789'],
            'CH valid E + 9 digits + TVA' => ['CHE123456789TVA'],
            'CH valid E + 9 digits + MWST' => ['CHE123456789MWST'],
            'CH valid E + 9 digits + IVA' => ['CHE123456789IVA'],
            'CH valid E + 9 digits + space + TVA' => ['CHE123456789 TVA'],
            'CH valid E + 9 digits + space + MWST' => ['CHE123456789 MWST'],
            'CH valid E + 9 digits + space + IVA' => ['CHE123456789 IVA'],
            'CH valid lowercase 6 digits' => ['ch123456'],
            'CH valid lowercase E + 9 digits' => ['che123456789'],
            'CH valid E + 9 digits + lowercase suffix' => ['CHE123456789tva'],
        ];
    }

    public static function invalidChVatFormatsProvider(): array
    {
        return [
            'CH invalid 5 digits' => ['CH12345'],
            'CH invalid 7 digits' => ['CH1234567'],
            'CH invalid E + 8 digits' => ['CHE12345678'],
            'CH invalid E + 10 digits' => ['CHE1234567890'],
            'CH invalid suffix' => ['CHE123456789XYZ'],
            'CH invalid partial suffix' => ['CHE123456789TV'],
            'CH invalid first letter' => ['CHA123456'],
            'CH invalid E + 9 digits + invalid suffix' => ['CHE123456789ABC'],
            'CH invalid E + 9 digits + space + invalid suffix' => ['CHE123456789 ABC'],
            'CH invalid double suffix' => ['CHE123456789TVAMWST'],
            'CH invalid special chars' => ['CH12-34-56'],
            'CH invalid missing E' => ['CH123456789TVA'],
        ];
    }

    public function testVatValidFormat(): void
    {
        self::assertTrue($this->validator->validateFormat('IT10648200011'));
    }

    public function testVatWrongFormat(): void
    {
        $vat_numbers = [
            '',
            'IT1234567890',
            'HU23395381',
            'IT12345',
            'foobar123',
        ];
        foreach ($vat_numbers as $vat) {
            self::assertFalse($this->validator->validateFormat($vat));
        }
    }

    public function testVatExist(): void
    {
        self::assertFalse($this->validator->validateExistence($this->fake_vat));
    }

    public function testVatValid(): void
    {
        self::assertFalse($this->validator->validate($this->fake_vat));
    }

    public function testLuhnCheck(): void
    {
        self::assertIsInt($this->validator->luhnCheck($this->fake_vat));
        self::assertNotEquals(0, $this->validator->luhnCheck($this->fake_vat));
    }

    public function testHuVatValidFormat(): void
    {
        self::assertTrue($this->validator->validateFormat('HU28395515'));
    }

    public function testHuVatInvalidFormat(): void
    {
        self::assertFalse($this->validator->validateFormat('HU28395514'));
    }
}

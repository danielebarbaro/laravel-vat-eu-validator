<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Orchestra\Testbench\TestCase;

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

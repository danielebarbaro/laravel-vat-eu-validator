<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use PHPUnit\Framework\TestCase;

class VatValidatorTest extends TestCase
{
    protected $validator;

    protected $fake_vat;

    public function setUp(): void
    {
        parent::setUp();

        $this->validator = new VatValidator();
        $this->fake_vat = 'IT12345678901';
    }

    public function testVatValidFormatFail()
    {
        self::assertFalse($this->validator->validateFormat($this->fake_vat));
    }

    public function testVatValidFormat()
    {
        self::assertTrue($this->validator->validateFormat('IT10648200011'));
    }

    public function testVatWrongFormat()
    {
        $vat_numbers = [
            '',
            'IT1234567890',
            'IT12345',
            'foobar123'
        ];
        foreach ($vat_numbers as $vat) {
            self::assertFalse($this->validator->validateFormat($vat));
        }
    }

    public function testVatExist()
    {
        self::assertFalse($this->validator->validateExistence($this->fake_vat));
    }

    public function testVatValid()
    {
        self::assertFalse($this->validator->validate($this->fake_vat));
    }

    public function testLuhnCheck(): void
    {
        self::assertIsInt($this->validator->luhnCheck($this->fake_vat));
        self::assertNotEquals($this->validator->luhnCheck($this->fake_vat), 0);
    }

}

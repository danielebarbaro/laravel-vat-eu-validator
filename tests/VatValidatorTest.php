<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatLookupResult;
use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
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

    public function testLookupAcceptsFullVatNumberAndReturnsDto(): void
    {
        $stubClient = $this->makeRecordingClient();

        $validator = new VatValidator($stubClient);

        $result = $validator->lookup(' ie6364992h ');

        self::assertSame(1, $stubClient->lookupCalls);
        self::assertSame('IE', $stubClient->receivedCountry);
        self::assertSame('6364992H', $stubClient->receivedNumber);

        self::assertInstanceOf(VatLookupResult::class, $result);
        self::assertSame('IE', $result->countryCode);
        self::assertSame('6364992H', $result->vatNumber);
        self::assertTrue($result->valid);
        self::assertSame('ACME LTD', $result->name);
        self::assertSame('1 Example Street', $result->address);
    }

    public function testLookupThrowsWithoutCallingApiWhenFormatIsInvalid(): void
    {
        $stubClient = $this->makeRecordingClient();

        $validator = new VatValidator($stubClient);

        try {
            $validator->lookup('IT12345');
            self::fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertStringContainsString('Invalid VAT number format', $e->getMessage());
            self::assertSame(0, $stubClient->lookupCalls, 'Client must not be called for invalid formats');
        }
    }

    private function makeRecordingClient(): ViesClientInterface
    {
        return new class () implements ViesClientInterface {
            public int $lookupCalls = 0;
            public ?string $receivedCountry = null;
            public ?string $receivedNumber = null;

            public function check(string $countryCode, string $vatNumber): bool
            {
                return true;
            }

            public function lookup(string $countryCode, string $vatNumber): array
            {
                $this->lookupCalls++;
                $this->receivedCountry = $countryCode;
                $this->receivedNumber = $vatNumber;

                return [
                    'countryCode' => $countryCode,
                    'vatNumber' => $vatNumber,
                    'valid' => true,
                    'name' => 'ACME LTD',
                    'address' => '1 Example Street',
                ];
            }
        };
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Functional;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Functional tests for VatValidator using SOAP client
 *
 * These tests make actual API calls to the EU VIES SOAP service.
 * The SOAP API allows querying real VAT numbers without authentication.
 *
 * @group functional
 */
class VatValidatorSoapFunctionalTest extends TestCase
{
    protected VatValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure to use SOAP client
        config(['vat-validator.client' => ViesSoapClient::CLIENT_NAME]);
        app()->forgetInstance(ViesClientInterface::class);
        app()->forgetInstance(VatValidator::class);

        $this->validator = app(VatValidator::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configure SOAP client with extended timeout for API calls
        $app['config']->set('vat-validator.client', ViesSoapClient::CLIENT_NAME);
        $app['config']->set('vat-validator.clients.' . ViesSoapClient::CLIENT_NAME . '.timeout', 30);
    }

    // ========================================
    // Configuration Tests
    // ========================================

    /**
     * Test that SOAP client is being used
     */
    public function testSoapClientIsUsed(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesSoapClient::class, $client);
    }

    // ========================================
    // Format Validation Tests (Local)
    // ========================================

    /**
     * Test VAT format validation with valid Italian VAT number
     */
    public function testValidateFormatWithValidItalianVat(): void
    {
        $result = $this->validator->validateFormat('IT00743110157');
        $this->assertTrue($result);
    }

    /**
     * Test VAT format validation with invalid VAT number
     */
    public function testValidateFormatWithInvalidVat(): void
    {
        $result = $this->validator->validateFormat('IT12345');
        $this->assertFalse($result);
    }

    // ========================================
    // SOAP API VAT Existence Tests
    // ========================================

    /**
     * Test VAT existence validation with valid VAT number
     *
     * Makes an actual SOAP API call to verify a known valid VAT number.
     * Uses Google Ireland's VAT number as a stable test case.
     */
    public function testValidateExistenceWithValidVat(): void
    {
        // Google Ireland - a well-known valid VAT number
        $result = $this->validator->validateExistence('IE6388047V');
        $this->assertTrue($result);
    }

    /**
     * Test VAT existence validation with invalid VAT number
     *
     * Makes an actual SOAP API call to verify an invalid VAT number.
     */
    public function testValidateExistenceWithInvalidVat(): void
    {
        // Invalid Italian VAT number
        $result = $this->validator->validateExistence('IT99999999999');
        $this->assertFalse($result);
    }

    /**
     * Test VAT existence validation with various EU countries
     *
     * Makes actual SOAP API calls to test various EU VAT number formats.
     * Result can be true or false depending on whether the number exists in VIES.
     *
     */
    #[DataProvider('vatNumbersForExistenceCheckProvider')]
    public function testValidateExistenceWithVariousCountries(string $vatNumber): void
    {
        $result = $this->validator->validateExistence($vatNumber);
        $this->assertIsBool($result);
    }

    // ========================================
    // Full Validation Tests (Format + Existence)
    // ========================================

    /**
     * Test full validation with valid VAT number using SOAP API
     *
     * Makes an actual SOAP API call through the VatValidator to validate a VAT number.
     * This test validates both format and existence.
     */
    public function testFullValidationWithValidVat(): void
    {
        // Valid Italian VAT that should pass both format and existence checks
        $result = $this->validator->validate('IT00743110157');
        $this->assertTrue($result);
    }

    /**
     * Test full validation with format-invalid VAT number
     *
     * Should fail at format validation stage without making an API call.
     */
    public function testFullValidationWithInvalidFormat(): void
    {
        $result = $this->validator->validate('IT12345');
        $this->assertFalse($result);
    }

    // ========================================
    // Data Providers
    // ========================================

    /**
     * Data provider for VAT existence check tests
     *
     * @return array<string, array<string>>
     */
    public static function vatNumbersForExistenceCheckProvider(): array
    {
        return [
            'Germany' => ['DE123456789'],
            'France' => ['FR12345678901'],
            'Spain' => ['ESA12345674'],
            'Netherlands' => ['NL123456789B01'],
            'Belgium' => ['BE0123456789'],
        ];
    }
}

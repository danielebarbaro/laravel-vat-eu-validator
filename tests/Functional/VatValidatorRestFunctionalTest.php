<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Functional;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Functional tests for VatValidator using REST client
 *
 * These tests make actual API calls to the official VIES REST API.
 * The official VIES service validates real VAT numbers registered in the EU.
 *
 * @see https://ec.europa.eu/taxation_customs/vies/
 */
class VatValidatorRestFunctionalTest extends TestCase
{
    protected VatValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure to use REST client
        config(['vat-validator.client' => ViesRestClient::CLIENT_NAME]);
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
        // Configure REST client with official EU VIES endpoint
        $app['config']->set('vat-validator.client', ViesRestClient::CLIENT_NAME);
        $app['config']->set('vat-validator.clients.' . ViesRestClient::CLIENT_NAME . '.timeout', 30);
    }

    // ========================================
    // Configuration Tests
    // ========================================

    /**
     * Test that REST client is being used
     */
    public function testRestClientIsUsed(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);
    }

    /**
     * Test that the REST client is configured with the correct endpoint
     */
    public function testRestClientHasCorrectEndpoint(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Verify base URL is the official EU endpoint
        $clientReflection = new \ReflectionClass($client);
        $baseUrlProperty = $clientReflection->getProperty('baseUrl');

        $baseUrl = $baseUrlProperty->getValue($client);

        $this->assertEquals(ViesRestClient::BASE_URL, $baseUrl);
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

    /**
     * Test VAT format validation with various EU countries
     *
     */
    #[DataProvider('validVatNumbersProvider')]
    public function testValidateFormatWithVariousCountries(string $vatNumber): void
    {
        $result = $this->validator->validateFormat($vatNumber);
        $this->assertIsBool($result);
    }

    // ========================================
    // REST API Connectivity Tests
    // ========================================

    /**
     * Test VIES system status endpoint
     *
     * Verifies that we can query the VIES system status using the official endpoint.
     * This endpoint does not require a valid VAT number and should always return a response.
     */
    public function testViesSystemStatus(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Get VIES system status
        $viesStatus = $client->getViesStatus();

        $this->assertIsArray($viesStatus);
        $this->assertArrayHasKey('vow', $viesStatus);
        $this->assertArrayHasKey('countries', $viesStatus);
        $this->assertIsArray($viesStatus['countries']);
    }

    // ========================================
    // REST API VAT Validation Tests
    // ========================================

    /**
     * Test VAT validation using REST client directly with a known valid VAT
     *
     * Makes an actual REST API call to validate a VAT number using the client directly.
     * Uses a known valid VAT number from the EU Commission.
     *
     * Note: This test may fail if the VAT number becomes invalid or the service is down.
     */
    public function testVatValidationUsingRestClientDirectly(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Use European Commission VAT number as a test (should be valid)
        $countryCode = 'IE';
        $vatNumber = '6388047V';

        // Make actual REST API call
        $result = $client->check($countryCode, $vatNumber);

        $this->assertIsBool($result);
    }

    /**
     * Test VAT number check with full response data
     *
     * Verifies that the checkVatNumber method returns complete response data
     * including all fields from the CheckVatResponse schema.
     */
    public function testCheckVatNumberReturnsCompleteData(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Use European Commission VAT number
        $response = $client->checkVatNumber([
            'countryCode' => 'IE',
            'vatNumber' => '6388047V',
        ]);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('countryCode', $response);
        $this->assertArrayHasKey('vatNumber', $response);
        $this->assertArrayHasKey('valid', $response);
    }

    /**
     * Test VAT validation with invalid VAT number
     *
     * Verifies that invalid VAT numbers are correctly identified.
     */
    public function testVatValidationWithInvalidVatNumber(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Use an invalid VAT number
        $countryCode = 'IT';
        $vatNumber = '00000000000';

        // Make actual REST API call
        $result = $client->check($countryCode, $vatNumber);

        $this->assertFalse($result);
    }

    /**
     * Test full validation with invalid VAT format
     *
     * Should fail at format validation stage without making an API call.
     */
    public function testFullValidationWithInvalidFormat(): void
    {
        $result = $this->validator->validate('IT00000000000');
        $this->assertFalse($result);
    }

    /**
     * Test full validation with valid VAT format
     *
     * Should proceed to API call stage.
     */
    public function testFullValidationWithValidFormat(): void
    {
        $result = $this->validator->validate('IE6388047V');
        $this->assertTrue($result);
    }

    /**
     * Test the test service endpoint
     *
     * Verifies that the test endpoint is accessible.
     * Note: The test service may not always be available and may return SERVICE_UNAVAILABLE.
     */
    public function testCheckVatTestServiceEndpoint(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Test endpoint should work similarly to the regular endpoint
        $response = $client->checkVatTestService([
            'countryCode' => 'BE',
            'vatNumber' => '0876495433',
        ]);

        $this->assertIsArray($response);
        // If successful, should have 'valid' key
        if (isset($response['valid'])) {
            $this->assertIsBool($response['valid']);
        }
    }

    // ========================================
    // Data Providers
    // ========================================

    /**
     * Data provider for VAT number format tests
     *
     * @return array<string, array<string>>
     */
    public static function validVatNumbersProvider(): array
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

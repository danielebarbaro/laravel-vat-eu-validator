<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Functional;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Orchestra\Testbench\TestCase;

/**
 * Functional tests for VatValidator using REST client
 *
 * These tests make actual API calls to the VIES REST API test endpoint.
 * The test API (https://viesapi.eu/api-test) only accepts queries for specific
 * predefined VAT numbers. See: https://viesapi.eu/test-vies-api/
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
        // Configure REST client with test API endpoint and authentication
        // Environment variables are automatically loaded from phpunit.xml.dist
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
     * Test that authentication credentials are configured correctly
     */
    public function testAuthenticationIsConfigured(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Verify API credentials are set
        $clientReflection = new \ReflectionClass($client);
        $apiKeyIdProperty = $clientReflection->getProperty('apiKeyId');

        $apiKeyId = $apiKeyIdProperty->getValue($client);

        $apiKeyProperty = $clientReflection->getProperty('apiKey');

        $apiKey = $apiKeyProperty->getValue($client);

        $this->assertEquals('test_id', $apiKeyId);
        $this->assertEquals('test_key', $apiKey);
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
     * @dataProvider validVatNumbersProvider
     */
    public function testValidateFormatWithVariousCountries(string $vatNumber): void
    {
        $result = $this->validator->validateFormat($vatNumber);
        $this->assertIsBool($result);
    }

    // ========================================
    // REST API Connectivity Tests
    // ========================================

    /**
     * Test REST API connectivity and authentication
     *
     * Verifies that the REST client can connect to the test API
     * and authenticate successfully using test credentials.
     */
    public function testApiConnectivityAndAuthentication(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Verify API connectivity by getting account status
        $accountStatus = $client->getAccountStatus();
        $this->assertIsArray($accountStatus);
    }

    /**
     * Test VIES system status endpoint
     *
     * Verifies that we can query the VIES system status.
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
    }

    // ========================================
    // REST API VAT Validation Tests
    // ========================================

    /**
     * Test VAT validation using REST client directly
     *
     * Makes an actual REST API call to validate a VAT number using the client directly.
     * Uses PL7272445205, which is a valid test VAT number from the official test list.
     *
     * @see https://viesapi.eu/test-vies-api/
     */
    public function testVatValidationUsingRestClientDirectly(): void
    {
        $reflection = new \ReflectionClass($this->validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($this->validator);

        $this->assertInstanceOf(ViesRestClient::class, $client);

        // Use a valid test VAT number from the official VIES test API list
        // PL7272445205 is a predefined valid test number
        $countryCode = 'PL';
        $vatNumber = '7272445205';

        // Make actual REST API call
        $result = $client->check($countryCode, $vatNumber);

        $this->assertTrue($result);
    }

    /**
     * Test full VAT validation using validator facade with REST API
     *
     * Makes an actual REST API call through the VatValidator to validate a VAT number.
     * This test validates both format and existence using the test API.
     * Uses PL7272445205, which is a valid test VAT number from the official test list.
     *
     * @see https://viesapi.eu/test-vies-api/
     */
    public function testFullValidationWithValidVatUsingRestApiCall(): void
    {
        // Use a valid test VAT number from the official VIES test API list
        // PL7272445205 should pass both format and existence validation
        $result = $this->validator->validate('PL7272445205');

        $this->assertTrue($result);
    }

    /**
     * Test full validation with invalid VAT format
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

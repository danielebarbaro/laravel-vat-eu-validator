<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
use Orchestra\Testbench\TestCase;

class ViesTest extends TestCase
{
    protected ViesClientInterface $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = resolve(ViesClientInterface::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testItBindsViesClientInterfaceToViesSoapClientImplementation(): void
    {
        // Test with default configuration (SOAP)
        $resolved = app(ViesClientInterface::class);

        $this->assertInstanceOf(ViesSoapClient::class, $resolved);
    }

    public function testItBindsViesClientInterfaceToViesRestClientWhenConfigured(): void
    {
        // Override configuration to use REST client
        config(['vat-validator.client' => ViesRestClient::CLIENT_NAME]);

        // Clear the resolved instance to force re-resolution
        app()->forgetInstance(ViesClientInterface::class);

        $resolved = app(ViesClientInterface::class);

        $this->assertInstanceOf(ViesRestClient::class, $resolved);
    }

    public function testVatValidatorInternallyUsesCorrectClient(): void
    {
        // Test with SOAP client (default)
        config(['vat-validator.client' => ViesSoapClient::CLIENT_NAME]);
        app()->forgetInstance(ViesClientInterface::class);
        app()->forgetInstance(VatValidator::class);

        $validator = app(VatValidator::class);

        $reflection = new \ReflectionClass($validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($validator);

        self::assertInstanceOf(ViesSoapClient::class, $client);
    }

    public function testVatValidatorInternallyUsesRestClientWhenConfigured(): void
    {
        // Test with REST client
        config(['vat-validator.client' => ViesRestClient::CLIENT_NAME]);
        app()->forgetInstance(ViesClientInterface::class);
        app()->forgetInstance(VatValidator::class);

        $validator = app(VatValidator::class);

        $reflection = new \ReflectionClass($validator);
        $clientProperty = $reflection->getProperty('client');

        $client = $clientProperty->getValue($validator);

        self::assertInstanceOf(ViesRestClient::class, $client);
    }

    public function testThrowsExceptionForUnknownClientType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown VIES client type: invalid');

        config(['vat-validator.client' => 'invalid']);
        app()->forgetInstance(ViesClientInterface::class);

        app(ViesClientInterface::class);
    }

    public function testClientEmptyDataException(): void
    {
        $this->expectException(ViesException::class);
        $this->client->check('', '');
    }

    public function testClientEmptyVatNumberException(): void
    {
        $this->expectException(ViesException::class);
        $this->client->check('IT', '');
    }

    public function testClientEmptyCountryException(): void
    {
        $this->expectException(ViesException::class);
        $this->client->check('', '12345');
    }

    public function testFailExistResponse(): void
    {
        $response = $this->client->check('IT', '12345');
        self::assertFalse($response);
    }
}

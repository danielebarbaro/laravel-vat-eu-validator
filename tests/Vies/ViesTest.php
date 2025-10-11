<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\VatValidator;
use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
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

    public function testItBindsViesClientInterfaceToViesSoapClientImplementation()
    {
        $resolved = app(ViesClientInterface::class);

        $this->assertInstanceOf(ViesSoapClient::class, $resolved);
    }

    public function testVatValidatorInternallyUsesCorrectClient(): void
    {
        // Risolvo il validator dal container
        $validator = app(VatValidator::class);

        // Uso reflection per verificare quale client è iniettato
        $reflection = new \ReflectionClass($validator);
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($validator);

        // Deve essere un'istanza di Client (SOAP)
        self::assertInstanceOf(ViesSoapClient::class, $client);
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

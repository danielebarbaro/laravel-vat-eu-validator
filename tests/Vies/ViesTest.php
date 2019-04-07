<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\Vies\Client;
use PHPUnit\Framework\TestCase;
use SoapClient;
use SoapFault;

class ViesTest extends TestCase
{
    protected $validator;

    protected $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();
    }

    public function testClientEmptyDataException()
    {
        $this->expectException(\Danielebarbaro\LaravelVatEuValidator\Vies\ViesException::class);
        $this->client->check('', '');
    }

    public function testClientEmptyVatNumberException()
    {
        $this->expectException(\Danielebarbaro\LaravelVatEuValidator\Vies\ViesException::class);
        $this->client->check('IT', '');
    }

    public function testClientEmptyCountryException()
    {
        $this->expectException(\Danielebarbaro\LaravelVatEuValidator\Vies\ViesException::class);
        $this->client->check('', '12345');
    }

    public function testFailExistResponse()
    {
        $response = $this->client->check('IT', '12345');
        self::assertFalse($response);
    }
}

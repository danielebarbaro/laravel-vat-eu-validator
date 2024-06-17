<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\Vies\Client;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use PHPUnit\Framework\TestCase;

class ViesTest extends TestCase
{
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();
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

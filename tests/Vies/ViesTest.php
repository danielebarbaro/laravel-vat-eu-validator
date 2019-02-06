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

    public function setUp()
    {
        parent::setUp();

        $this->client = new Client();
    }

    /**
     * @expectedException \Danielebarbaro\LaravelVatEuValidator\Vies\ViesException
     */
    public function testClientEmptyDataException()
    {
        $this->client->check('', '');
    }

    /**
     * @expectedException \Danielebarbaro\LaravelVatEuValidator\Vies\ViesException
     */
    public function testClientEmptyVatNumberException()
    {
        $this->client->check('IT', '');
    }

    /**
     * @expectedException \Danielebarbaro\LaravelVatEuValidator\Vies\ViesException
     */
    public function testClientEmptyCountryException()
    {
        $this->client->check('', '12345');
    }

    public function testFailExistResponse()
    {
        $response = $this->client->check('IT', '12345');
        self::assertFalse($response);
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use SoapClient;
use SoapFault;

class Client
{

    /**
     * @const string
     */
    const URL = 'http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

    /**
     * @var int
     */
    protected $timeout;

    /**
     * @var SoapClient
     */
    private $client;

    /**
     * Client constructor.
     *
     * @param int $timeout
     */
    public function __construct(int $timeout = 10)
    {
        $this->timeout = $timeout;
    }

    /**
     * Check via Vies the VAT number
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return bool
     *
     * @throws ViesException
     */
    public function check(string $countryCode, string $vatNumber): bool
    {
        try {
            $response = $this->getClient()->checkVat(
                array(
                    'countryCode' => $countryCode,
                    'vatNumber' => $vatNumber
                )
            );
        } catch (SoapFault $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }

        return $response->valid;
    }

    /**
     * Create SoapClient
     * @return SoapClient
     */
    protected function getClient(): SoapClient
    {
        if ($this->client === null) {
            $this->client = new SoapClient(self::URL, ['connection_timeout' => $this->timeout]);
        }

        return $this->client;
    }
}

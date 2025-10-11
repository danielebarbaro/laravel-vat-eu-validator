<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use SoapClient;
use SoapFault;

class ViesSoapClient implements ViesClientInterface
{
    /**
     * @const string
     */
    public const URL = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

    private ?\SoapClient $client = null;

    /**
     * Client constructor.
     *
     * @param int $timeout
     */
    public function __construct(protected int $timeout = 10)
    {
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
                [
                    'countryCode' => $countryCode,
                    'vatNumber' => $vatNumber,
                ]
            );
        } catch (SoapFault $soapFault) {
            throw new ViesException($soapFault->getMessage(), $soapFault->getCode());
        }

        return $response->valid;
    }

    /**
     * Create SoapClient
     * @return SoapClient
     */
    protected function getClient(): SoapClient
    {
        if (! $this->client instanceof \SoapClient) {
            $this->client = new SoapClient(self::URL, ['connection_timeout' => $this->timeout]);
        }

        return $this->client;
    }
}

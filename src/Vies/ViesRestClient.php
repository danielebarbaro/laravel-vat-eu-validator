<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ViesRestClient implements ViesClientInterface
{
    /**
     * Official EU VIES REST API base URL
     *
     * @const string
     */
    public const BASE_URL = 'https://ec.europa.eu/taxation_customs/vies/rest-api';

    /**
     * @const string
     */
    public const CLIENT_NAME = 'rest';

    /**
     * Client constructor.
     *
     * @param string $baseUrl
     * @param int $timeout
     */
    public function __construct(
        protected string $baseUrl = self::BASE_URL,
        protected int $timeout = 10,
    ) {
    }

    /**
     * Get the HTTP client
     *
     * @return PendingRequest
     */
    protected function getClient(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->acceptJson()
            ->contentType('application/json');
    }

    /**
     * Check via Vies REST API the VAT number
     *
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return bool
     *
     * @throws ViesException
     */
    public function check(string $countryCode, string $vatNumber): bool
    {
        $data = $this->checkVatNumber([
            'countryCode' => $countryCode,
            'vatNumber' => $vatNumber,
        ]);

        if (isset($data['valid'])) {
            return (bool) $data['valid'];
        }

        throw new ViesException('Invalid response format from VIES REST API');
    }

    /**
     * Check a VAT number for a specific country
     *
     * Official endpoint: POST /check-vat-number
     *
     * @param array $requestData Request body according to CheckVatRequest schema
     * @return array CheckVatResponse data
     * @throws ViesException
     */
    public function checkVatNumber(array $requestData): array
    {
        return $this->performVatCheck('/check-vat-number', $requestData);
    }

    /**
     * Test the check VAT service
     *
     * Official endpoint: POST /check-vat-test-service
     *
     * @param array $requestData Request body according to CheckVatRequest schema
     * @return array CheckVatResponse data
     * @throws ViesException
     */
    public function checkVatTestService(array $requestData): array
    {
        return $this->performVatCheck('/check-vat-test-service', $requestData);
    }

    /**
     * Perform a VAT check request to the specified endpoint
     *
     * @param string $endpoint
     * @param array $requestData
     * @return array
     * @throws ViesException
     */
    private function performVatCheck(string $endpoint, array $requestData): array
    {
        try {
            $response = $this->getClient()
                ->post("{$this->baseUrl}{$endpoint}", $requestData);

            if ($response->failed()) {
                $data = $response->json();

                // Handle error response according to CommonResponse schema
                if (isset($data['actionSucceed']) && $data['actionSucceed'] === false) {
                    $errorMessage = $this->formatErrorMessage($data);

                    throw new ViesException($errorMessage, $response->status());
                }

                throw new ViesException(
                    'VIES REST API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Format error message from CommonResponse
     *
     * @param array $data
     * @return string
     */
    private function formatErrorMessage(array $data): string
    {
        if (isset($data['errorWrappers']) && is_array($data['errorWrappers'])) {
            $errors = array_map(function ($wrapper) {
                $error = $wrapper['error'] ?? 'Unknown error';
                $message = $wrapper['message'] ?? '';

                return $message ? "{$error}: {$message}" : $error;
            }, $data['errorWrappers']);

            return 'VIES API errors: ' . implode(', ', $errors);
        }

        return 'VIES API request failed';
    }

    /**
     * Get VIES system status and member states availability
     *
     * Official endpoint: GET /check-status
     *
     * @return array StatusInformationResponse data
     * @throws ViesException
     */
    public function getViesStatus(): array
    {
        try {
            $response = $this->getClient()
                ->get("{$this->baseUrl}/check-status");

            if ($response->failed()) {
                $data = $response->json();

                if (isset($data['actionSucceed']) && $data['actionSucceed'] === false) {
                    $errorMessage = $this->formatErrorMessage($data);

                    throw new ViesException($errorMessage, $response->status());
                }

                throw new ViesException(
                    'VIES REST API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            return $response->json();
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }
}

<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ViesRestClient implements ViesClientInterface
{
    /**
     * @const string
     */
    public const BASE_URL = 'https://viesapi.eu/api';

    /**
     * @const string
     */
    public const CLIENT_NAME = 'rest';

    /**
     * Client constructor.
     *
     * @param string $baseUrl
     * @param int $timeout
     * @param string $apiKeyId API key identifier for Basic Authentication
     * @param string $apiKey API key for Basic Authentication
     */
    public function __construct(
        protected string $apiKeyId,
        protected string $apiKey,
        protected string $baseUrl = self::BASE_URL,
        protected int $timeout = 10,
    ) {
    }

    /**
     * Get the HTTP client with authentication
     *
     * @return PendingRequest
     */
    protected function getClient(): PendingRequest
    {
        $client = Http::timeout($this->timeout)
            ->acceptJson();

        // Apply authentication if credentials are provided
        if ($this->hasAuthentication()) {
            $client = $this->withAuthentication($client);
        }

        return $client;
    }

    /**
     * Check if authentication credentials are available
     *
     * @return bool
     */
    private function hasAuthentication(): bool
    {
        return ! empty($this->apiKeyId) && ! empty($this->apiKey);
    }

    /**
     * Apply authentication to the HTTP client
     * VIES API uses Basic Authentication with key_id as username and key as password
     *
     * @param PendingRequest $client
     * @return PendingRequest
     */
    private function withAuthentication(PendingRequest $client): PendingRequest
    {
        // Method 2: Basic Authentication
        // Authorization: Basic base64(key_id:key)
        return $client->withBasicAuth($this->apiKeyId, $this->apiKey);
    }

    /**
     * Check via Vies REST API the VAT number
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return bool
     *
     * @throws ViesException
     */
    public function check(string $countryCode, string $vatNumber): bool
    {
        $data = $this->getVatInfo($countryCode, $vatNumber);

        if (isset($data['valid'])) {
            return (bool)$data['valid'];
        }

        throw new ViesException('Invalid response format from VIES REST API');
    }

    /**
     * Get detailed VAT information
     *
     * @param string $countryCode
     * @param string $vatNumber
     * @param bool $parsed Whether to get parsed address components
     * @return array
     * @throws ViesException
     */
    private function getVatInfo(string $countryCode, string $vatNumber, bool $parsed = false): array
    {
        try {
            $euVatNumber = $countryCode . $vatNumber;

            $endpoint = $parsed
                ? "/get/vies/parsed/euvat/{$euVatNumber}"
                : "/get/vies/euvat/{$euVatNumber}";

            $response = $this->getClient()
                ->get("{$this->baseUrl}{$endpoint}");

            if ($response->failed()) {
                throw new ViesException(
                    'VIES REST API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            // Handle error response
            if (isset($data['error'])) {
                throw new ViesException(
                    $data['error']['description'] ?? 'VIES API error',
                    $data['error']['code'] ?? 0
                );
            }

            // Return VIES data
            if (isset($data['vies'])) {
                return $data['vies'];
            }

            throw new ViesException('Invalid response format from VIES REST API');
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get account status
     *
     * @return array
     * @throws ViesException
     */
    public function getAccountStatus(): array
    {
        try {
            $response = $this->getClient()
                ->get("{$this->baseUrl}/check/account/status");

            if ($response->failed()) {
                throw new ViesException(
                    'VIES REST API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            // Handle error response
            if (isset($data['error'])) {
                throw new ViesException(
                    $data['error']['description'] ?? 'VIES API error',
                    $data['error']['code'] ?? 0
                );
            }

            return $data['account'] ?? $data;
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get VIES system status
     *
     * @return array
     * @throws ViesException
     */
    public function getViesStatus(): array
    {
        try {
            $response = $this->getClient()
                ->get("{$this->baseUrl}/check/vies/status");

            if ($response->failed()) {
                throw new ViesException(
                    'VIES REST API request failed: ' . $response->body(),
                    $response->status()
                );
            }

            $data = $response->json();

            // Handle error response
            if (isset($data['error'])) {
                throw new ViesException(
                    $data['error']['description'] ?? 'VIES API error',
                    $data['error']['code'] ?? 0
                );
            }

            return $data['vies'] ?? $data;
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }
}

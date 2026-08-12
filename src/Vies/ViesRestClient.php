<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
     * Look up the full VIES record for a VAT number.
     *
     * Returns the raw CheckVatResponse payload from the official endpoint,
     * which contains valid, name, address, trader-* and match fields.
     *
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return array<string, mixed>
     *
     * @throws ViesException
     */
    public function lookup(string $countryCode, string $vatNumber): array
    {
        return $this->checkVatNumber([
            'countryCode' => $countryCode,
            'vatNumber' => $vatNumber,
        ]);
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

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
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

            return $this->parseResponse($response);
        } catch (ConnectionException $e) {
            throw new ViesException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Parse a VIES response, throwing on transport-level failures and
     * on application-level failures (HTTP 200 with actionSucceed=false).
     *
     * @return array<string, mixed>
     * @throws ViesException
     */
    private function parseResponse(Response $response): array
    {
        $data = $response->json();
        $isErrorBody = is_array($data) && ($data['actionSucceed'] ?? null) === false;

        if ($response->failed() || $isErrorBody) {
            throw $this->buildException($response, $data, $isErrorBody);
        }

        if (! is_array($data)) {
            throw new ViesException(
                'Invalid response format from VIES REST API',
                $response->status()
            );
        }

        return $data;
    }

    /**
     * Build a ViesException for a failed response, attaching any error codes
     * extracted from the CommonResponse.errorWrappers payload.
     */
    private function buildException(Response $response, mixed $data, bool $isErrorBody): ViesException
    {
        if ($isErrorBody && is_array($data)) {
            $codes = $this->extractErrorCodes($data);

            $exception = new ViesException(
                $this->formatErrorMessage($data),
                $response->status() ?: 0
            );

            return $exception->setErrorCodes($codes);
        }

        return new ViesException(
            'VIES REST API request failed: ' . $response->body(),
            $response->status()
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return string[]
     */
    private function extractErrorCodes(array $data): array
    {
        if (! isset($data['errorWrappers']) || ! is_array($data['errorWrappers'])) {
            return [];
        }

        $codes = [];

        foreach ($data['errorWrappers'] as $wrapper) {
            if (is_array($wrapper) && isset($wrapper['error']) && is_string($wrapper['error'])) {
                $codes[] = $wrapper['error'];
            }
        }

        return $codes;
    }

    /**
     * Format error message from CommonResponse
     *
     * @param array $data
     * @return string
     */
    private function formatErrorMessage(array $data): string
    {
        if (isset($data['errorWrappers']) && is_array($data['errorWrappers']) && $data['errorWrappers'] !== []) {
            $errors = array_map(function ($wrapper) {
                if (! is_array($wrapper)) {
                    return 'Unknown error';
                }

                $error = isset($wrapper['error']) && is_string($wrapper['error']) && $wrapper['error'] !== ''
                    ? $wrapper['error']
                    : 'Unknown error';
                $message = isset($wrapper['message']) && is_string($wrapper['message']) ? $wrapper['message'] : '';

                return $message !== '' ? "{$error}: {$message}" : $error;
            }, $data['errorWrappers']);

            return 'VIES API errors: ' . implode(', ', $errors);
        }

        return 'VIES API request failed';
    }
}

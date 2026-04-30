<?php

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

use Exception;

class ViesException extends Exception
{
    /**
     * Known VIES API error codes returned in the CommonResponse.errorWrappers
     * array when actionSucceed is false.
     *
     * @link https://ec.europa.eu/taxation_customs/vies/#/technical-information
     */
    public const ERROR_INVALID_INPUT = 'INVALID_INPUT';
    public const ERROR_INVALID_REQUESTER_INFO = 'INVALID_REQUESTER_INFO';
    public const ERROR_SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    public const ERROR_MS_UNAVAILABLE = 'MS_UNAVAILABLE';
    public const ERROR_TIMEOUT = 'TIMEOUT';
    public const ERROR_VAT_BLOCKED = 'VAT_BLOCKED';
    public const ERROR_IP_BLOCKED = 'IP_BLOCKED';
    public const ERROR_GLOBAL_MAX_CONCURRENT_REQ = 'GLOBAL_MAX_CONCURRENT_REQ';
    public const ERROR_GLOBAL_MAX_CONCURRENT_REQ_TIME = 'GLOBAL_MAX_CONCURRENT_REQ_TIME';
    public const ERROR_MS_MAX_CONCURRENT_REQ = 'MS_MAX_CONCURRENT_REQ';
    public const ERROR_MS_MAX_CONCURRENT_REQ_TIME = 'MS_MAX_CONCURRENT_REQ_TIME';

    /**
     * Error codes considered transient — caller may retry after a backoff.
     */
    public const TRANSIENT_ERRORS = [
        self::ERROR_SERVICE_UNAVAILABLE,
        self::ERROR_MS_UNAVAILABLE,
        self::ERROR_TIMEOUT,
        self::ERROR_GLOBAL_MAX_CONCURRENT_REQ,
        self::ERROR_GLOBAL_MAX_CONCURRENT_REQ_TIME,
        self::ERROR_MS_MAX_CONCURRENT_REQ,
        self::ERROR_MS_MAX_CONCURRENT_REQ_TIME,
    ];

    /**
     * @var string[]
     */
    private array $errorCodes = [];

    /**
     * @param string[] $errorCodes
     */
    public function setErrorCodes(array $errorCodes): self
    {
        $this->errorCodes = array_values(array_filter(
            $errorCodes,
            static fn (string $code): bool => $code !== ''
        ));

        return $this;
    }

    /**
     * @return string[]
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    public function hasErrorCode(string $code): bool
    {
        return in_array($code, $this->errorCodes, true);
    }

    public function isTransient(): bool
    {
        foreach ($this->errorCodes as $code) {
            if (in_array($code, self::TRANSIENT_ERRORS, true)) {
                return true;
            }
        }

        return false;
    }
}

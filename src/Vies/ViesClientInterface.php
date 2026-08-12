<?php

declare(strict_types=1);

namespace Danielebarbaro\LaravelVatEuValidator\Vies;

interface ViesClientInterface
{
    /**
     * Check via Vies the VAT number
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return bool
     *
     * @throws ViesException
     */
    public function check(string $countryCode, string $vatNumber): bool;

    /**
     * Look up the full VIES record for a VAT number.
     *
     * Returns the raw response data from the underlying VIES service
     * (e.g. countryCode, vatNumber, valid, name, address, requestDate, ...).
     *
     * @param string $countryCode
     * @param string $vatNumber
     *
     * @return array<string, mixed>
     *
     * @throws ViesException
     */
    public function lookup(string $countryCode, string $vatNumber): array;
}

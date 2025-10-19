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
}

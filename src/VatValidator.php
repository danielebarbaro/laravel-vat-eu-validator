<?php

namespace Danielebarbaro\LaravelVatEuValidator;

use Danielebarbaro\LaravelVatEuValidator\Vies\Client;

class VatValidator
{
    /**
     * Regular expression patterns per country code
     *
     * @var array
     * @link http://ec.europa.eu/taxation_customs/vies/faq.html?locale=en#item_11
     */
    protected static array $pattern_expression = [
        'AT' => 'U[A-Z\d]{8}',
        'BE' => '(0\d{9}|\d{10})',
        'BG' => '\d{9,10}',
        'CY' => '\d{8}[A-Z]',
        'CZ' => '\d{8,10}',
        'DE' => '\d{9}',
        'DK' => '(\d{2} ?){3}\d{2}',
        'EE' => '\d{9}',
        'EL' => '\d{9}',
        'ES' => '[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8}',
        'FI' => '\d{8}',
        'FR' => '([A-Z]{2}|\d{2})\d{9}',
        'GB' => '\d{9}|\d{12}|(GD|HA)\d{3}',
        'HR' => '\d{11}',
        'HU' => '\d{8}',
        'IE' => '[A-Z\d]{8}|[A-Z\d]{9}',
        'IT' => '\d{11}',
        'LT' => '(\d{9}|\d{12})',
        'LU' => '\d{8}',
        'LV' => '\d{11}',
        'MT' => '\d{8}',
        'NL' => '\d{9}B\d{2}',
        'PL' => '\d{10}',
        'PT' => '\d{9}',
        'RO' => '\d{2,10}',
        'SE' => '\d{12}',
        'SI' => '\d{8}',
        'SK' => '\d{10}',
    ];

    /**
     * VatValidator constructor.
     * @param Client|null $client
     */
    public function __construct(private ?Client $client = null)
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client();
        }
    }

    /**
     * Return if a country is supported by this validator
     * @param string $country
     * @return bool
     */
    public static function countryIsSupported(string $country): bool
    {
        return isset(self::$pattern_expression[$country]);
    }

    /**
     * Validate a VAT number format.
     * @param string $vatNumber
     * @return bool
     */
    public function validateFormat(string $vatNumber): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);
        [$country, $number] = $this->splitVat($vatNumber);


        if (! isset(self::$pattern_expression[$country])) {
            return false;
        }

        $validate_rule = preg_match('/^' . self::$pattern_expression[$country] . '$/', (string) $number) > 0;

        if ($validate_rule && $country === 'IT') {
            $result = self::luhnCheck($number);

            return $result % 10 == 0;
        }

        return $validate_rule;
    }

    /**
     * Check existence VAT number
     * @param string $vatNumber
     * @return bool
     * @throws Vies\ViesException
     */
    public function validateExistence(string $vatNumber): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);
        $result = $this->validateFormat($vatNumber);

        if ($result) {
            [$country, $number] = $this->splitVat($vatNumber);
            $result = $this->client->check($country, $number);
        }

        return $result;
    }

    /**
     * A php implementation of Luhn Algo
     *
     * @link https://en.wikipedia.org/wiki/Luhn_algorithm
     * @param  string  $vat
     * @return int
     */
    public static function luhnCheck(string $vat): int
    {
        $sum = 0;
        $vat_array = str_split($vat);
        $counter = count($vat_array);
        for ($index = 0; $index < $counter; ++$index) {
            $value = intval($vat_array[$index]);
            if ($index % 2 !== 0) {
                $value *= 2;
                if ($value > 9) {
                    $value = 1 + ($value % 10);
                }
            }

            $sum += $value;
        }

        return $sum;
    }

    /**
     * Validates a VAT number .
     *
     * @param string $vatNumber Either the full VAT number (incl. country).
     * @return bool
     * @throws Vies\ViesException
     */
    public function validate(string $vatNumber): bool
    {
        return $this->validateFormat($vatNumber) && $this->validateExistence($vatNumber);
    }

    /**
     * @param string $vatNumber
     * @return string
     */
    private function vatCleaner(string $vatNumber): string
    {
        $vatNumber_no_spaces = trim($vatNumber);

        return strtoupper($vatNumber_no_spaces);
    }

    /**
     * @param string $vatNumber
     * @return array
     */
    private function splitVat(string $vatNumber): array
    {
        return [
            substr($vatNumber, 0, 2),
            substr($vatNumber, 2),
        ];
    }
}

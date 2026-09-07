<?php

namespace Danielebarbaro\LaravelVatEuValidator;

use Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface;

class VatValidator
{
    /**
     * Regular expression patterns per country code
     *
     * @var array<string, string>
     * @link http://ec.europa.eu/taxation_customs/vies/faq.html?locale=en#item_11
     */
    protected static array $pattern_expression = [
        'AT' => 'U[A-Z\d]{8}',
        'BE' => '[01]\d{9}',
        'BG' => '\d{9,10}',
        'CY' => '\d{8}[A-Z]',
        'CZ' => '\d{8,10}',
        'DE' => '\d{9}',
        'DK' => '\d{8}',
        'EE' => '\d{9}',
        'EL' => '\d{9}',
        'ES' => '(?:[A-Z]\d{7}[A-Z]|\d{8}[A-Z]|[A-Z]\d{8})',
        'FI' => '\d{8}',
        'FR' => '[0-9A-HJ-NP-Z]{2}[0-9]{9}',
        'HR' => '\d{11}',
        'HU' => '\d{8}',
        'IE' => '(?:\d{7}[A-W][A-W]?|\d[A-Z\d]\d{5}[A-W])',
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
        'XI' => '(?:\d{9}|\d{12}|(?:GD|HA)\d{3})',
    ];

    /**
     * VatValidator constructor.
     */
    public function __construct(private readonly ViesClientInterface $client)
    {
    }

    /**
     * Return if a country is supported by this validator
     */
    public static function countryIsSupported(string $country): bool
    {
        return isset(self::$pattern_expression[$country]);
    }

    /**
     * Validate a VAT number format.
     */
    public function validateFormat(string $vatNumber, ?string $defaultCountry = null): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);
        [$country, $number] = $this->splitVat($vatNumber, $defaultCountry);

        if (! isset(self::$pattern_expression[$country])) {
            return false;
        }

        $validate_rule = preg_match('/^' . self::$pattern_expression[$country] . '$/', (string) $number) > 0;

        if ($validate_rule && $country === 'FR') {
            return $this->validateFrVat($number);
        }
        
        if ($validate_rule && $country === 'IT') {
            return $this->validateItVat($number);
        }

        if ($validate_rule && $country === 'HU') {
            return $this->validateHuVat($number);
        }

        return $validate_rule;
    }

    /**
     * Check existence VAT number in VIES database
     *
     * @throws Vies\ViesException
     */
    public function validateExistence(string $vatNumber, ?string $defaultCountry = null): bool
    {
        $vatNumber = $this->vatCleaner($vatNumber);
        
        if (! $this->validateFormat($vatNumber, $defaultCountry)) {
            return false;
        }

        [$country, $number] = $this->splitVat($vatNumber, $defaultCountry);

        return $this->client->check($country, $number);
    }

    /**
     * Validates a Hungarian VAT number.
     */
    protected function validateHuVat(string $vatNumber): bool
    {
        $checksum = (int) $vatNumber[7];
        $weights = [9, 7, 3, 1, 9, 7, 3];
        $sum = 0;

        foreach ($weights as $i => $weight) {
            $sum += (int) $vatNumber[$i] * $weight;
        }

        $calculatedChecksum = (10 - ($sum % 10)) % 10;

        return $calculatedChecksum === $checksum;
    }

    /**
     * Validates an Italian VAT number.
     */
    protected function validateItVat(string $vatNumber): bool
    {
        if (!preg_match('/^[0-9]{11}$/', $vatNumber)) {
            return false;
        }

        if ($vatNumber === '00000000000') {
            return false;
        }

        $officeCode = (int) substr($vatNumber, 7, 3);
        $validOffice = ($officeCode >= 1 && $officeCode <= 100) 
            || in_array($officeCode, [120, 121, 888, 999], true);

        if (!$validOffice) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = (int) $vatNumber[$i];
            
            if ($i % 2 === 0) {
                $sum += $digit;
            } else {
                $double = $digit * 2;
                $sum += ($double > 9) ? ($double - 9) : $double;
            }
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $vatNumber[10];
    }

    /**
     * Validates a French VAT number.
     */
    protected function validateFrVat(string $vatNumber): bool
    {
        // 1. Strict format check: 2 alphanumeric characters followed by 9 digits
        if (!preg_match('/^[0-9A-HJ-NP-Z]{2}[0-9]{9}$/', $vatNumber)) {
            return false;
        }

        $key = substr($vatNumber, 0, 2);
        $siren = substr($vatNumber, 2);

        // 2. Mathematical check on 2-digit numeric keys (Modulo 97)
        if (ctype_digit($key)) {
            $sirenInt = (int) $siren;
            $computed = (12 + 3 * ($sirenInt % 97)) % 97;

            if (sprintf('%02d', $computed) !== $key) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a VAT number (format + VIES existence).
     *
     * @throws Vies\ViesException
     */
    public function validate(string $vatNumber, ?string $defaultCountry = null): bool
    {
        return $this->validateExistence($vatNumber, $defaultCountry);
    }

    /**
     * Cleans a VAT number by removing spaces, dots, dashes and converting to uppercase.
     */
    private function vatCleaner(string $vatNumber): string
    {
        $clean = preg_replace('/[\s\.\-]+/', '', $vatNumber);

        return strtoupper((string) $clean);
    }

    /**
     * Splits a VAT number into its country code and the rest of the number.
     *
     * @return array{0: string, 1: string}
     */
    private function splitVat(string $vatNumber, ?string $defaultCountry = null): array
    {
        $prefix = substr($vatNumber, 0, 2);

        if ($prefix === 'GR') {
            $prefix = 'EL';
        }

        // 1. If a default country is provided, we check if the VAT number matches its pattern.
        if ($defaultCountry !== null) {
            $defaultCountry = strtoupper($defaultCountry);
            if ($defaultCountry === 'GR') {
                $defaultCountry = 'EL';
            }

            // If the string starts EXPLICITEMENT by the default country's prefix or another supported country's prefix,
            // AND the remaining length corresponds to a number without a prefix.
            if ($prefix === $defaultCountry) {
                return [
                    $defaultCountry,
                    substr($vatNumber, 2),
                ];
            }

            // If the detected prefix is a supported country but different from the defaultCountry,
            // we check if the complete string (without cutting anything) matches the pattern of the defaultCountry.
            if (self::countryIsSupported($defaultCountry)) {
                $pattern = '/^(?:' . self::$pattern_expression[$defaultCountry] . ')$/';
                if (preg_match($pattern, $vatNumber) === 1) {
                    // The complete number matches the local format of the defaultCountry !
                    return [
                        $defaultCountry,
                        $vatNumber,
                    ];
                }
            }
        }

        // 2. If the extracted prefix is a supported country (standard behavior without defaultCountry)
        if (self::countryIsSupported($prefix)) {
            return [
                $prefix,
                substr($vatNumber, 2),
            ];
        }

        // 3. Fallback on defaultCountry if it was provided but didn't match the pattern above
        if ($defaultCountry !== null) {
            return [
                $defaultCountry,
                $vatNumber,
            ];
        }

        return [
            $prefix,
            substr($vatNumber, 2),
        ];
    }
}
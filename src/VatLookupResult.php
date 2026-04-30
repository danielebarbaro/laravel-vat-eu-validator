<?php

declare(strict_types=1);

namespace Danielebarbaro\LaravelVatEuValidator;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Typed result of a VIES VAT lookup.
 *
 * Common fields (countryCode, vatNumber, valid, name, address, requestDate)
 * are populated for both SOAP and REST clients. The trader-* and
 * requestIdentifier fields are only returned by the REST client and
 * will be null when the SOAP client is in use.
 */
final readonly class VatLookupResult
{
    public function __construct(
        public string $countryCode,
        public string $vatNumber,
        public bool $valid,
        public ?string $name,
        public ?string $address,
        public ?DateTimeImmutable $requestDate,
        public ?string $requestIdentifier,
        public ?string $traderName,
        public ?string $traderStreet,
        public ?string $traderPostalCode,
        public ?string $traderCity,
        public ?string $traderCompanyType,
        public ?string $traderNameMatch,
        public ?string $traderStreetMatch,
        public ?string $traderPostalCodeMatch,
        public ?string $traderCityMatch,
        public ?string $traderCompanyTypeMatch,
    ) {
    }

    /**
     * Build the DTO from a raw VIES response payload (REST or SOAP).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['countryCode'] ?? ''),
            vatNumber: (string) ($data['vatNumber'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            name: self::stringOrNull($data['name'] ?? null),
            address: self::stringOrNull($data['address'] ?? null),
            requestDate: self::parseDate($data['requestDate'] ?? null),
            requestIdentifier: self::stringOrNull($data['requestIdentifier'] ?? null),
            traderName: self::stringOrNull($data['traderName'] ?? null),
            traderStreet: self::stringOrNull($data['traderStreet'] ?? null),
            traderPostalCode: self::stringOrNull($data['traderPostalCode'] ?? null),
            traderCity: self::stringOrNull($data['traderCity'] ?? null),
            traderCompanyType: self::stringOrNull($data['traderCompanyType'] ?? null),
            traderNameMatch: self::stringOrNull($data['traderNameMatch'] ?? null),
            traderStreetMatch: self::stringOrNull($data['traderStreetMatch'] ?? null),
            traderPostalCodeMatch: self::stringOrNull($data['traderPostalCodeMatch'] ?? null),
            traderCityMatch: self::stringOrNull($data['traderCityMatch'] ?? null),
            traderCompanyTypeMatch: self::stringOrNull($data['traderCompanyTypeMatch'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'vatNumber' => $this->vatNumber,
            'valid' => $this->valid,
            'name' => $this->name,
            'address' => $this->address,
            'requestDate' => $this->requestDate?->format(DateTimeInterface::ATOM),
            'requestIdentifier' => $this->requestIdentifier,
            'traderName' => $this->traderName,
            'traderStreet' => $this->traderStreet,
            'traderPostalCode' => $this->traderPostalCode,
            'traderCity' => $this->traderCity,
            'traderCompanyType' => $this->traderCompanyType,
            'traderNameMatch' => $this->traderNameMatch,
            'traderStreetMatch' => $this->traderStreetMatch,
            'traderPostalCodeMatch' => $this->traderPostalCodeMatch,
            'traderCityMatch' => $this->traderCityMatch,
            'traderCompanyTypeMatch' => $this->traderCompanyTypeMatch,
        ];
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = (string) $value;

        return $string === '' ? null : $string;
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }
}

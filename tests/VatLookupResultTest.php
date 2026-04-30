<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests;

use Danielebarbaro\LaravelVatEuValidator\VatLookupResult;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class VatLookupResultTest extends TestCase
{
    public function testFromArrayMapsRestResponseShape(): void
    {
        $payload = [
            'countryCode' => 'IE',
            'vatNumber' => '6364992H',
            'requestDate' => '2026-04-30T09:32:20.253Z',
            'valid' => true,
            'requestIdentifier' => '',
            'name' => 'ADOBE SYSTEMS SOFTWARE IRELAND LTD',
            'address' => '4 - 6 RIVER WALK, CITYWEST BUSINESS CAMPUS, SAGGART, DUBLIN 24',
            'traderName' => '---',
            'traderStreet' => '---',
            'traderPostalCode' => '---',
            'traderCity' => '---',
            'traderCompanyType' => '---',
            'traderNameMatch' => 'NOT_PROCESSED',
            'traderStreetMatch' => 'NOT_PROCESSED',
            'traderPostalCodeMatch' => 'NOT_PROCESSED',
            'traderCityMatch' => 'NOT_PROCESSED',
            'traderCompanyTypeMatch' => 'NOT_PROCESSED',
        ];

        $result = VatLookupResult::fromArray($payload);

        self::assertSame('IE', $result->countryCode);
        self::assertSame('6364992H', $result->vatNumber);
        self::assertTrue($result->valid);
        self::assertSame('ADOBE SYSTEMS SOFTWARE IRELAND LTD', $result->name);
        self::assertSame('4 - 6 RIVER WALK, CITYWEST BUSINESS CAMPUS, SAGGART, DUBLIN 24', $result->address);

        self::assertInstanceOf(DateTimeImmutable::class, $result->requestDate);
        self::assertSame('2026-04-30', $result->requestDate->format('Y-m-d'));

        self::assertNull($result->requestIdentifier, 'empty strings should be null');
        self::assertSame('---', $result->traderName);
        self::assertSame('NOT_PROCESSED', $result->traderNameMatch);
    }

    public function testFromArrayMapsSoapResponseShapeWithMissingTraderFields(): void
    {
        $payload = [
            'countryCode' => 'IE',
            'vatNumber' => '6364992H',
            'requestDate' => '2026-04-30+02:00',
            'valid' => true,
            'name' => 'ADOBE SYSTEMS SOFTWARE IRELAND LTD',
            'address' => 'DUBLIN 24',
        ];

        $result = VatLookupResult::fromArray($payload);

        self::assertSame('IE', $result->countryCode);
        self::assertTrue($result->valid);
        self::assertSame('ADOBE SYSTEMS SOFTWARE IRELAND LTD', $result->name);
        self::assertInstanceOf(DateTimeImmutable::class, $result->requestDate);

        self::assertNull($result->requestIdentifier);
        self::assertNull($result->traderName);
        self::assertNull($result->traderStreet);
        self::assertNull($result->traderNameMatch);
    }

    public function testFromArrayHandlesUnparseableDate(): void
    {
        $result = VatLookupResult::fromArray([
            'countryCode' => 'IE',
            'vatNumber' => '6364992H',
            'valid' => false,
            'requestDate' => 'not-a-date',
        ]);

        self::assertNull($result->requestDate);
        self::assertFalse($result->valid);
    }

    public function testToArrayRoundTripsTheDto(): void
    {
        $result = VatLookupResult::fromArray([
            'countryCode' => 'IE',
            'vatNumber' => '6364992H',
            'valid' => true,
            'name' => 'ACME',
            'address' => '1 Example St',
            'requestDate' => '2026-04-30T09:32:20+00:00',
        ]);

        $array = $result->toArray();

        self::assertSame('IE', $array['countryCode']);
        self::assertSame('6364992H', $array['vatNumber']);
        self::assertTrue($array['valid']);
        self::assertSame('ACME', $array['name']);
        self::assertSame('2026-04-30T09:32:20+00:00', $array['requestDate']);
        self::assertNull($array['traderName']);
    }
}

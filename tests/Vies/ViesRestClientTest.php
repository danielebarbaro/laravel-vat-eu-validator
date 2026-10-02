<?php

declare(strict_types=1);

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ViesRestClientTest extends TestCase
{
    private const ENDPOINT = ViesRestClient::BASE_URL . '/check-vat-number';

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testValidNumberReturnsTrue(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['countryCode' => 'IT', 'vatNumber' => '00743110157', 'valid' => true])]);

        $this->assertTrue((new ViesRestClient())->check('IT', '00743110157'));
    }

    public function testInvalidNumberReturnsFalse(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['countryCode' => 'IT', 'vatNumber' => '00743110158', 'valid' => false])]);

        $this->assertFalse((new ViesRestClient())->check('IT', '00743110158'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function viesErrors(): array
    {
        return [
            'MS_UNAVAILABLE' => ['MS_UNAVAILABLE'],
            'MS_MAX_CONCURRENT_REQ' => ['MS_MAX_CONCURRENT_REQ'],
            'SERVICE_UNAVAILABLE' => ['SERVICE_UNAVAILABLE'],
            'TIMEOUT' => ['TIMEOUT'],
            'GLOBAL_MAX_CONCURRENT_REQ' => ['GLOBAL_MAX_CONCURRENT_REQ'],
        ];
    }

    #[DataProvider('viesErrors')]
    public function testErrorStateInsideAnInvalidResponseThrows(string $error): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'valid' => false,
            'actionSucceed' => false,
            'errorWrappers' => [['error' => $error]],
        ])]);

        $this->expectException(ViesException::class);
        $this->expectExceptionMessage("VIES API errors: {$error}");

        (new ViesRestClient())->check('IT', '00743110157');
    }

    public function testErrorWrappersAloneThrow(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'valid' => false,
            'errorWrappers' => [['error' => 'MS_UNAVAILABLE', 'message' => 'Member State not available']],
        ])]);

        $this->expectException(ViesException::class);
        $this->expectExceptionMessage('VIES API errors: MS_UNAVAILABLE: Member State not available');

        (new ViesRestClient())->check('IT', '00743110157');
    }

    public function testFailedActionWithoutDetailsThrows(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['valid' => false, 'actionSucceed' => false])]);

        $this->expectException(ViesException::class);
        $this->expectExceptionMessage('VIES API request failed');

        (new ViesRestClient())->check('IT', '00743110157');
    }

    public function testErrorStateOnHttpFailureStillThrows(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'actionSucceed' => false,
            'errorWrappers' => [['error' => 'SERVICE_UNAVAILABLE']],
        ], 503)]);

        $this->expectException(ViesException::class);
        $this->expectExceptionCode(503);

        (new ViesRestClient())->check('IT', '00743110157');
    }
}

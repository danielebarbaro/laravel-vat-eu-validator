<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\VatValidatorServiceProvider;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class ViesRestClientTest extends TestCase
{
    private ViesRestClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new ViesRestClient();
    }

    protected function getPackageProviders($app): array
    {
        return [
            VatValidatorServiceProvider::class,
        ];
    }

    public function testCheckVatNumberThrowsWhenResponseHas200ButActionSucceedFalse(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response([
                'actionSucceed' => false,
                'errorWrappers' => [
                    ['error' => 'MS_MAX_CONCURRENT_REQ'],
                ],
            ], 200),
        ]);

        try {
            $this->client->checkVatNumber([
                'countryCode' => 'IT',
                'vatNumber' => '00743110157',
            ]);
            $this->fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertStringContainsString('MS_MAX_CONCURRENT_REQ', $e->getMessage());
            self::assertSame(['MS_MAX_CONCURRENT_REQ'], $e->getErrorCodes());
            self::assertTrue($e->hasErrorCode(ViesException::ERROR_MS_MAX_CONCURRENT_REQ));
            self::assertTrue($e->isTransient());
        }
    }

    public function testCheckThrowsForActionSucceedFalseWithMultipleErrors(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response([
                'actionSucceed' => false,
                'errorWrappers' => [
                    ['error' => 'MS_UNAVAILABLE', 'message' => 'Member State unavailable'],
                    ['error' => 'TIMEOUT'],
                ],
            ], 200),
        ]);

        try {
            $this->client->check('DE', '123456789');
            $this->fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertSame(['MS_UNAVAILABLE', 'TIMEOUT'], $e->getErrorCodes());
            self::assertStringContainsString('MS_UNAVAILABLE: Member State unavailable', $e->getMessage());
            self::assertStringContainsString('TIMEOUT', $e->getMessage());
            self::assertTrue($e->isTransient());
        }
    }

    public function testCheckThrowsForNonTransientError(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response([
                'actionSucceed' => false,
                'errorWrappers' => [
                    ['error' => 'INVALID_INPUT'],
                ],
            ], 400),
        ]);

        try {
            $this->client->check('XX', '0');
            $this->fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertTrue($e->hasErrorCode(ViesException::ERROR_INVALID_INPUT));
            self::assertFalse($e->isTransient());
            self::assertSame(400, $e->getCode());
        }
    }

    public function testCheckReturnsValidWhenResponseSucceeds(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response([
                'countryCode' => 'IE',
                'vatNumber' => '6388047V',
                'valid' => true,
                'name' => 'GOOGLE IRELAND LIMITED',
                'address' => '3RD FLOOR, GORDON HOUSE, BARROW STREET',
            ], 200),
        ]);

        self::assertTrue($this->client->check('IE', '6388047V'));
    }

    public function testGetViesStatusThrowsOnActionSucceedFalse(): void
    {
        Http::fake([
            '*/check-status' => Http::response([
                'actionSucceed' => false,
                'errorWrappers' => [
                    ['error' => 'SERVICE_UNAVAILABLE'],
                ],
            ], 200),
        ]);

        try {
            $this->client->getViesStatus();
            $this->fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertTrue($e->hasErrorCode(ViesException::ERROR_SERVICE_UNAVAILABLE));
            self::assertTrue($e->isTransient());
        }
    }

    public function testCheckThrowsOnNonJsonOrEmptyBody(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response('not json', 200),
        ]);

        $this->expectException(ViesException::class);
        $this->expectExceptionMessage('Invalid response format from VIES REST API');

        $this->client->checkVatNumber([
            'countryCode' => 'IT',
            'vatNumber' => '00743110157',
        ]);
    }

    public function testCheckThrowsOnHttpErrorWithoutErrorBody(): void
    {
        Http::fake([
            '*/check-vat-number' => Http::response('Internal Server Error', 500),
        ]);

        try {
            $this->client->checkVatNumber([
                'countryCode' => 'IT',
                'vatNumber' => '00743110157',
            ]);
            $this->fail('Expected ViesException was not thrown');
        } catch (ViesException $e) {
            self::assertSame(500, $e->getCode());
            self::assertStringContainsString('Internal Server Error', $e->getMessage());
            self::assertSame([], $e->getErrorCodes());
        }
    }
}

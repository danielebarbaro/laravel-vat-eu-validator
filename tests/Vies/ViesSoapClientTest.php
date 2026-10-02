<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Vies;

use Danielebarbaro\LaravelVatEuValidator\Vies\ViesException;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
use PHPUnit\Framework\TestCase;
use SoapClient;
use SoapFault;

/**
 * SoapClient bounds only the connect phase with connection_timeout; reading
 * the response is bounded by default_socket_timeout, which the client sets to
 * the configured timeout for the duration of the call.
 */
class ViesSoapClientTest extends TestCase
{
    private string $originalSocketTimeout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalSocketTimeout = (string) ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '60');
    }

    protected function tearDown(): void
    {
        ini_set('default_socket_timeout', $this->originalSocketTimeout);

        parent::tearDown();
    }

    public function testTimeoutBoundsTheCallAndIsRestoredAfterwards(): void
    {
        $soap = $this->fakeSoapClient(static fn (): object => (object) ['valid' => true]);

        $this->assertTrue($this->viesClient($soap, 7)->check('IT', '00743110157'));
        $this->assertSame('7', $soap->socketTimeoutDuringCall);
        $this->assertSame('60', ini_get('default_socket_timeout'));
    }

    public function testTimeoutIsRestoredWhenTheCallFails(): void
    {
        $soap = $this->fakeSoapClient(static function (): never {
            throw new SoapFault('Client', 'Error Fetching http headers');
        });

        try {
            $this->viesClient($soap, 3)->check('IT', '00743110157');
            $this->fail('A ViesException was expected');
        } catch (ViesException $viesException) {
            $this->assertSame('Error Fetching http headers', $viesException->getMessage());
        }

        $this->assertSame('3', $soap->socketTimeoutDuringCall);
        $this->assertSame('60', ini_get('default_socket_timeout'));
    }

    private function viesClient(SoapClient $soap, int $timeout): ViesSoapClient
    {
        return new class ($soap, $timeout) extends ViesSoapClient {
            public function __construct(private readonly SoapClient $soap, int $timeout)
            {
                parent::__construct($timeout);
            }

            protected function getClient(): SoapClient
            {
                return $this->soap;
            }
        };
    }

    /**
     * @param \Closure(): object $answer
     */
    private function fakeSoapClient(\Closure $answer): SoapClient
    {
        return new class ($answer) extends SoapClient {
            public ?string $socketTimeoutDuringCall = null;

            public function __construct(private readonly \Closure $answer)
            {
                parent::__construct(null, ['location' => 'http://localhost', 'uri' => 'urn:test']);
            }

            public function checkVat(array $request): object
            {
                $this->socketTimeoutDuringCall = (string) ini_get('default_socket_timeout');

                return ($this->answer)();
            }
        };
    }
}

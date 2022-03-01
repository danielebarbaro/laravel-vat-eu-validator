<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Orchestra\Testbench\TestCase;

class VatNumberFormatTest extends TestCase
{
    protected VatNumberFormat $rule;

    protected string $fake_vat;

    public function setUp(): void
    {
        parent::setUp();

        $this->rule = new VatNumberFormat();
        $this->fake_vat = 'IT12345678901';
    }

    public function testSuccessVatNumberFormat()
    {
        self::assertFalse($this->rule->passes('vat_number_format', $this->fake_vat));
        self::assertTrue($this->rule->passes('vat_number_format', 'IT10648200011'));
    }

    public function testFailVatNumberFormat()
    {
        self::assertFalse($this->rule->passes('vat_number_format', 'foo'));
    }

    public function testFailVatNumberFormatMessage()
    {
        self::assertStringContainsString('VAT number :attribute  not exist.', $this->rule->message());
    }
}

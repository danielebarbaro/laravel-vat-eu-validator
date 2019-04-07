<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;
use Orchestra\Testbench\TestCase;

class VatNumberTest extends TestCase
{
    protected $rule;

    protected $fake_vat;

    public function setUp(): void
    {
        parent::setUp();

        $this->rule = new VatNumber();
        $this->fake_vat = 'IT12345678901';
    }

    public function testSuccessVatNumber()
    {
        self::assertFalse($this->rule->passes('vat_number', $this->fake_vat));
    }

    public function testSuccessVatNumberMessage()
    {
        self::assertStringContainsString('The :attribute must be a valid VAT number.', $this->rule->message());
    }
}

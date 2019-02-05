<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat;
use Orchestra\Testbench\TestCase;

class VatNumberFormatTest extends TestCase
{
    protected $rule;

    protected $fake_vat;

    public function setUp()
    {
        parent::setUp();

        $this->rule = new VatNumberFormat();
        $this->fake_vat = 'IT12345678901';
    }

    public function testSuccessVatNumberFormat()
    {
        self::assertTrue($this->rule->passes('vat_number_format', $this->fake_vat));
    }

    public function testFailVatNumberFormat()
    {
        self::assertFalse($this->rule->passes('vat_number_format', 'foo'));
    }
}

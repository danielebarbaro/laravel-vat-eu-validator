<?php

namespace Danielebarbaro\LaravelVatEuValidator\Tests\Rules;

use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist;
use Orchestra\Testbench\TestCase;

class VatNumberExistTest extends TestCase
{
    protected $rule;

    protected $fake_vat;

    public function setUp()
    {
        parent::setUp();

        $this->rule = new VatNumberExist();
        $this->fake_vat = 'IT12345678901';
    }

    public function testSuccessVatNumberFormatExist()
    {
        self::assertFalse($this->rule->passes('vat_number_exist', $this->fake_vat));
    }
}

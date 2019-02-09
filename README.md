Laravel VAT EU VALIDATOR
================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)
[![Build Status](https://img.shields.io/travis/danielebarbaro/laravel-vat-eu-validator/master.svg?style=flat-square)](https://travis-ci.org/danielebarbaro/laravel-vat-eu-validator)
[![Total Downloads](https://img.shields.io/packagist/dt/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)

laravel-vat-eu-validator is a package inspired from [vat.php](https://github.com/dannyvankooten/vat.php) to validate a VAT number for businesses based in Europe.


## Installation

You can install the package via composer:

```bash
composer require danielebarbaro/laravel-vat-eu-validator
```

The package will automatically register itself.

## Usage

```php
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;

// Check VAT format and VIES existence
VatValidator::validate('IT12345');

// Check VAT format
VatValidator::validateFormat('IT12345678901'); 

// Check VAT existence
VatValidator::validateExistence('IT12345678901');

```


#### Validation

The package registers two new validation rules.

**vat_number**

The field under validation must be a valid and existing VAT number.

**vat_number_exist**

The field under validation check id is an existing VAT number.

**vat_number_format**

The field under validation must be a valid VAT number.

```php
use Illuminate\Http\Request;

class Controller {

    public function foo(Request $request) 
    {
        $request->validate([
            'bar_field' => ['vat_number'],
        ]);
        
        $request->validate([
            'bar_field' => ['vat_number_exist'],
        ]);
        
        $request->validate([
               'bar_field' => ['vat_number_format'],
       ]);
    }
}
```

Alternatively, you can also use the `Rule` directly.

```php
use Illuminate\Http\Request;
use Danielebarbaro\LaravelVatEuValidator\Rules;

class Controller {

    public function foo(Request $request) 
    {
        $request->validate([
            'bar_field' => [ new Rules\VatNumber() ],
            'bar_field' => [ new Rules\VatNumberExist() ],
            'bar_field' => [ new Rules\VatNumberFormat() ],
        ]);
    }
}
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email barbaro.daniele@gmail.com instead of using the issue tracker.

## Credits

- [Daniele Barbaro](https://github.com/danielebarbaro)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).

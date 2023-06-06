Laravel VAT EU VALIDATOR
================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/danielebarbaro/laravel-vat-eu-validator/run-tests?label=tests)](https://github.com/danielebarbaro/laravel-vat-eu-validator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/danielebarbaro/laravel-vat-eu-validator/Check%20&%20fix%20styling?label=code%20style)](https://github.com/danielebarbaro/laravel-vat-eu-validator/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)

laravel-vat-eu-validator is a package inspired from [vat.php](https://github.com/dannyvankooten/vat.php) to validate a VAT number for businesses based in Europe.

#### For Laravel 5,6,7 use tag 0.5.4

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

### Translations
Just add and customize validation strings in `lang/en/validation.php`
```
    ...
    'vat_number' => 'The :attribute must be a valid VAT number.',
    'vat_number_format' => 'The :attribute must be write in a valid number format {country_name}{vat_number}.',
    'vat_number_exist' => 'VAT number :attribute not exist.',
    ...
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

## Contributors
- [Alessio Nobile](https://github.com/alessionobile)
- [Javier Núñez](https://github.com/javiernunez)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).

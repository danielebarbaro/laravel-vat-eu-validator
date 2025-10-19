Laravel VAT EU VALIDATOR
================

[![Latest Version on Packagist](https://img.shields.io/packagist/v/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/danielebarbaro/laravel-vat-eu-validator/run-tests?label=tests)](https://github.com/danielebarbaro/laravel-vat-eu-validator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/danielebarbaro/laravel-vat-eu-validator/Check%20&%20fix%20styling?label=code%20style)](https://github.com/danielebarbaro/laravel-vat-eu-validator/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/danielebarbaro/laravel-vat-eu-validator.svg?style=flat-square)](https://packagist.org/packages/danielebarbaro/laravel-vat-eu-validator)

laravel-vat-eu-validator is a package inspired from [vat.php](https://github.com/dannyvankooten/vat.php) to validate a VAT number for businesses based in Europe.

#### For Laravel 10, 11, 12 use tag 2.x
#### For Laravel 8, 9 use tag 1.20
#### For Laravel 5, 6, 7 use tag 0.5.4

## Installation

You can install the package via composer:

```bash
composer require danielebarbaro/laravel-vat-eu-validator
```

The package will automatically register itself.

## Configuration

### VIES Client Configuration

The package supports multiple VIES clients for VAT validation:

- **SOAP Client** (default): Uses the traditional SOAP API
- **REST Client**: Uses the modern REST API

To customize the client configuration, publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-vat-eu-validator-config
```

This will create a `config/vat-validator.php` file where you can configure which client to use:

```php
<?php

use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;

return [
    // Select which client to use for VIES validation
    // Available: ViesSoapClient::CLIENT_NAME, ViesRestClient::CLIENT_NAME
    'client' => ViesSoapClient::CLIENT_NAME, // Default: SOAP

    'clients' => [
        ViesSoapClient::CLIENT_NAME => [
            'timeout' => 10,
        ],
        ViesRestClient::CLIENT_NAME => [
            'timeout' => 10,
            'base_url' => ViesRestClient::BASE_URL,
        ],
    ],
];
```

#### Switching to REST Client

To use the REST client instead of SOAP, update your `config/vat-validator.php`:

```php
'client' => ViesRestClient::CLIENT_NAME,
```

The REST client uses the official European Commission VIES REST API endpoints, which do not require authentication or API keys.

#### Customizing REST Client Configuration

You can customize the REST client timeout and base URL if needed:

```php
'clients' => [
    ViesRestClient::CLIENT_NAME => [
        'timeout' => 10, // seconds
        'base_url' => env('VIES_REST_BASE_URL', ViesRestClient::BASE_URL),
    ],
],
```

By default, the client uses the official EU endpoint: `https://ec.europa.eu/taxation_customs/vies/rest-api`

#### Customizing Timeout

You can adjust the timeout for API requests:

```php
'clients' => [
    ViesSoapClient::CLIENT_NAME => [
        'timeout' => 30, // seconds
    ],
],
```

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
            'bar_field' => [new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber()],
        ]);
        
        $request->validate([
            'bar_field' => [new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist()],
        ]);
        
        $request->validate([
            'bar_field' => [new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat()],
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
            'bar_field' => [ new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber() ],
            'bar_field' => [ new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist() ],
            'bar_field' => [ new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat() ],
        ]);
    }
}
```

or

```php
use Illuminate\Http\Request;
use Danielebarbaro\LaravelVatEuValidator\Rules;

class Controller {

    public function foo(Request $request)
    {
        $request->validate([
            'bar_field' => [
                new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber(),
                new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist(),
                new \Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat(),
            ],
        ]);
    }
}
```

or

```php
use Illuminate\Http\Request;
use Danielebarbaro\LaravelVatEuValidator\Rules;

class Controller {

    public function foo(Request $request)
    {
        $request->validate([
            'bar_field' => [
                'vat_number',
                'vat_number_format',
                'vat_number_exist',
            ],
        ]);
    }
}
```

### Translations
Most of the displayed strings are defined in the `vatEuValidator::validation` translation files. The package ships with a few supported locales, but if yours is not yet included we would greatly appreciate a PR.

If not already published, you can edit or fill the translation files using `php artisan vendor:publish --tag=laravel-vat-eu-validator-lang`, this will copy our translation files to your app's `vendor/laravelVatEuValidator` "lang" path.

### Testing

```bash
# Run unit tests
composer test

# Run functional tests (makes actual API calls)
composer test-functional
```

For detailed testing documentation, see [tests/README.md](tests/README.md).

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email barbaro.daniele@gmail.com instead of using the issue tracker.

## Credits

- [Daniele Barbaro](https://daniele.barbaro.online)

## Contributors
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).

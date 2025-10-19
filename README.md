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

#### REST Client Authentication

The REST client supports authentication via API key using Basic Authentication. According to the VIES API documentation, you need both an **API Key ID** (identifier) and an **API Key** (secret).

To configure authentication, add both credentials to your `.env` file:

```env
VIES_API_KEY_ID=your-api-key-id-here
VIES_API_KEY=your-api-key-secret-here
```

For the test environment, you can use:

```env
VIES_API_KEY_ID=test_id
VIES_API_KEY=test_key
```

The credentials will be automatically loaded from the environment and used for HTTP Basic Authentication with the VIES REST API. The client sends the API Key ID as the username and the API Key as the password, as per the [VIES API specification](https://viesapi.eu).

**Note:** The VIES API documentation recommends using HMAC SHA256 authentication for production environments for enhanced security. However, this implementation uses Basic Authentication (Method 2) which is simpler and suitable for most use cases.

If you need to customize the API key configuration, you can modify the published config file:

```php
'clients' => [
    ViesRestClient::CLIENT_NAME => [
        'timeout' => 10,
        'base_url' => ViesRestClient::BASE_URL,
        'api_key' => env('VIES_API_KEY'), // Your API key
    ],
],
```

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

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Daniele Barbaro](https://github.com/danielebarbaro)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

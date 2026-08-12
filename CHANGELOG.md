# Changelog

All notable changes to `laravel-vat-eu-validator` will be documented in this file

## VAT lookup DTO and typed VIES error handling - 2026-04-30

### ✨ New Features

- **`VatValidator::lookup()`**: returns a typed `VatLookupResult` DTO with the
  full VIES record (countryCode, vatNumber, valid, name, address, requestDate,
  and the REST-only trader / match fields). The VAT format is validated
  locally first — an invalid format throws a `ViesException` without calling
  the API.
- **`ViesClientInterface::lookup()`**: new method on both the SOAP and REST
  clients returning the raw VIES payload as an array. Implementers of a
  custom client must add this method.

### 🐛 Bug Fixes

- **REST client now detects application-level errors on HTTP 200**: the VIES
  REST API can return `200 OK` with a body of
  `{"actionSucceed": false, "errorWrappers": [{"error": "MS_MAX_CONCURRENT_REQ"}]}`.
  Previously this slipped through as a successful response; it now throws
  `ViesException` with the formatted message and error codes attached.
  Affects both `/check-vat-number` and `/check-status`.

### 🔍 Typed error codes on `ViesException`

- All known VIES API error codes are now constants on `ViesException`:
  `INVALID_INPUT`, `INVALID_REQUESTER_INFO`, `SERVICE_UNAVAILABLE`,
  `MS_UNAVAILABLE`, `TIMEOUT`, `VAT_BLOCKED`, `IP_BLOCKED`,
  `GLOBAL_MAX_CONCURRENT_REQ`, `GLOBAL_MAX_CONCURRENT_REQ_TIME`,
  `MS_MAX_CONCURRENT_REQ`, `MS_MAX_CONCURRENT_REQ_TIME`.
- New helpers: `getErrorCodes()`, `hasErrorCode(string)`, `isTransient()` —
  letting callers retry on rate-limit / service-unavailable and fail fast on
  permanent errors.

```php
try {
    $result = VatValidator::lookup('IT00743110157');
} catch (ViesException $e) {
    if ($e->isTransient()) {
        // Retry with backoff
    }
}
```

### 🧪 Testing

- New unit tests in `tests/Vies/ViesRestClientTest.php` covering the
  HTTP-200-with-`actionSucceed=false` path, multiple error wrappers, the
  status endpoint, non-JSON bodies, and HTTP error responses without a VIES
  error body.
- New unit tests in `tests/VatLookupResultTest.php` covering the DTO mapping
  for both REST and SOAP response shapes.

## Laravel 13 support - 2026-03-28

Thanks to @sergix44 , we now have compatibility with Laravel 13! 🚀

## REST API Support - 2026-02-28

### Changelog v3.0.0

#### 🚀 Breaking Changes

##### Architecture: ViesClientInterface and Strategy Pattern

- **Removed `Vies\Client` class**: replaced by the `Vies\ViesClientInterface` interface and two concrete implementations (`ViesSoapClient`, `ViesRestClient`).
- **`VatValidator` constructor breaking change**: the constructor now requires a mandatory `ViesClientInterface` parameter (no longer `?Client $client = null` with internal fallback). Dependency injection is handled by the Service Provider.
- The `$client` property in `VatValidator` is now `private readonly ViesClientInterface`.

##### Updated Service Provider

- `VatValidatorServiceProvider` has been refactored to:
  - Register the `ViesClientInterface` binding based on configuration
  - Automatically resolve the correct client (SOAP or REST) from the container
  - Publish the new `vat-validator.php` configuration file
  

#### ✨ New Features

##### REST Client for VIES API

- **New `ViesRestClient`**: HTTP client using the official European Commission VIES REST API (`https://ec.europa.eu/taxation_customs/vies/rest-api`).
  - No authentication or API key required
  - Does not require the `ext-soap` extension (uses native HTTP)
  - Configurable timeout
  - Configurable base URL (useful for testing/mocking)
  - `ViesRestClient::BASE_URL` constant for the official endpoint
  - `ViesRestClient::CLIENT_NAME` constant for client identification
  

##### SOAP Client renamed and refactored

- **`Vies\Client` → `Vies\ViesSoapClient`**: the original SOAP client has been renamed and now implements `ViesClientInterface`.
  - `ViesSoapClient::CLIENT_NAME` constant for client identification
  - Configurable timeout via config
  

##### Publishable configuration file

- **New `config/vat-validator.php`**: allows choosing which client to use and configuring its parameters.
  - `'client'` → selects the active client (`ViesSoapClient::CLIENT_NAME` or `ViesRestClient::CLIENT_NAME`)
  - `'clients'` → per-client configuration (timeout, base_url)
  - Publishable with: `php artisan vendor:publish --tag=laravel-vat-eu-validator-config`
  

#### 🧪 Testing

##### Complete test suite restructuring

- **Test suite separation**: tests are now split into `unit` and `functional`:
  
  - `tests/` (unit) — mocked tests, run in CI
  - `tests/Functional/` — tests making actual API calls to VIES
  
- **New test files**:
  
  - `tests/Functional/VatValidatorRestFunctionalTest.php` — functional tests for the REST client
  - `tests/Functional/VatValidatorSoapFunctionalTest.php` — functional tests for the SOAP client
  - `tests/Rules/VatNumberExistTest.php` — tests for the VatNumberExist rule
  - `tests/Rules/VatNumberFormatTest.php` — tests for the VatNumberFormat rule
  - `tests/Rules/VatNumberTest.php` — tests for the VatNumber rule
  - `tests/VatValidatorFacadeTest.php` — tests for the facade
  - `tests/VatValidatorTest.php` — refactored VatValidator tests
  - `tests/Vies/ViesTest.php` — tests for the VIES clients
  
- **New test documentation**: `tests/README.md` with a complete testing guide
  
- **Updated Composer scripts**:
  
  - `composer test` → runs only the `unit` test suite
  - `composer test-functional` → runs only the `functional` test suite
  

##### PHPUnit updated

- `phpunit.xml.dist` completely restructured with readable formatting
- Added `displayDetailsOnTestsThatTriggerWarnings="true"` option
- `unit` test suite excludes `tests/Functional` directory
- `functional` test suite targets only `tests/Functional`
- CI runs only `vendor/bin/phpunit --testsuite=unit`

#### 📦 Dependencies and compatibility

##### Composer

- `ext-openssl` and `ext-soap` reordered (alphabetical order)
- `orchestra/testbench` updated to more specific minimum versions: `^v8.37.0|^v9.16.0|^v10.9.0`

##### CI test matrix

- Added PHP 8.4 and 8.5 support to the matrix
- Excluded incompatible combinations (PHP 8.4 + Laravel 10, PHP 8.5 + Laravel 10/11)

#### 📝 Upgrade Guide (from v2.x to v3.0)

##### If you use the package without customizations

No action required. The package works out-of-the-box with SOAP as the default, exactly as before.

##### If you were instantiating `VatValidator` or `Client` manually

```php
// ❌ Before (v2.x)
use Danielebarbaro\LaravelVatEuValidator\Vies\Client;
$validator = new VatValidator(new Client());

// ✅ After (v3.0)
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesSoapClient;
$validator = new VatValidator(new ViesSoapClient());

// Or with REST
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;
$validator = new VatValidator(new ViesRestClient());


```
##### If you want to switch to the REST client

1. Publish the configuration: `php artisan vendor:publish --tag=laravel-vat-eu-validator-config`
2. Update `config/vat-validator.php`:

```php
'client' => ViesRestClient::CLIENT_NAME,


```
##### If your tests reference `Vies\Client`

Update references to `ViesSoapClient` or use `ViesClientInterface` for mocking.

Special thanks to @claudiocenghialta for contributing the REST client support and the architectural refactoring that made this release possible.

## Allow overriding messages on validation - 2025-10-09

Thanks to @Gybra, it’s now possible to override validation rule messages.

## Laravel 12 support - 2025-03-15

Thanks to @laravel-shift, we now have compatibility with Laravel 12! 🚀
A big shoutout to @mbardelmeijer, @it-can, and @chellmann for the push to get this update out! 😊

## Add CH vat Validator - 2025-02-26

Add CH vat Validator thx to @jeroen-marinus

## Improved Validator Extension - 2025-01-28

**Improved Validator Extension**: Refactored the `Validator::extend()` and `Validator::replacer()` methods to enhance the flexibility and maintainability of custom validation rules. These changes ensure better integration and easier customization when extending validation logic.

A heartfelt thank you to [frknakk](https://github.com/frknakk) and [vazaha-nl](https://github.com/vazaha-nl) for their contributions and support in improving this package!

## Fix php8.4 warnings - 2025-01-05

thx to @it-can

## Add Hungarian VAT number validation - 2024-11-13

- Implement checksum validation for Hungarian VAT numbers.
- Update `VatValidator.php` to include Hungarian VAT validation logic.
- Add tests for Hungarian VAT number validation in `VatValidatorTest.php`.

Thx to @bsh

## Language files have been introduced - 2024-09-16

Thanks to @dualklip, language files have been introduced.

## Error messages fixes - 2024-07-02

Fix Swap error messages Exist and Format. Thx to @roerlemans

## L11 Support - 2024-06-18

I refactor the code during a MeetUp in Torino. Now I'm supporting L10 and L11 with php 8.2.

## 1.0.0 - 201X-XX-XX

- initial release

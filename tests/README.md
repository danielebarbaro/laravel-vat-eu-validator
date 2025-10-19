# Testing Documentation

This document provides detailed information about testing the Laravel VAT EU Validator package.

## Test Suites

The package includes two test suites:

### 1. Unit Tests (Default)
Unit tests mock external dependencies and test the package logic in isolation.

```bash
# Run unit tests
composer test

# Or directly with PHPUnit
vendor/bin/phpunit --testsuite=unit
```

### 2. Functional Tests
Functional tests make actual API calls to the official European Commission VIES services to validate VAT numbers.

```bash
# Run functional tests
composer test-functional

# Or directly with PHPUnit
vendor/bin/phpunit --testsuite=functional

# Run specific functional test files
vendor/bin/phpunit tests/Functional/VatValidatorSoapFunctionalTest.php
vendor/bin/phpunit tests/Functional/VatValidatorRestFunctionalTest.php
```

## Functional Test Configuration

### REST Client Tests

The REST client uses the **official European Commission VIES REST API** which does not require authentication or API keys:

- **Endpoint**: `https://ec.europa.eu/taxation_customs/vies/rest-api`
- **Authentication**: None required
- **Documentation**: https://ec.europa.eu/taxation_customs/vies/

The REST functional tests validate real VAT numbers registered in the EU VIES system.

### SOAP Client Tests

The SOAP client uses the traditional VIES SOAP service:

- **Endpoint**: `http://ec.europa.eu/taxation_customs/vies/services/checkVatService`
- **Authentication**: None required

## Testing with Real VAT Numbers

Both SOAP and REST functional tests use real VAT numbers from the European Commission and other EU institutions. These tests may occasionally fail if:

- The VIES service is temporarily unavailable
- A VAT number becomes invalid or is deregistered
- Network connectivity issues occur

This is normal for functional tests that depend on external services.

## Functional Test Organization

Functional tests are organized into clear sections:

### VatValidatorRestFunctionalTest.php

- **Configuration Tests** - Verify REST client setup and authentication
- **Format Validation Tests** - Test VAT format validation (local, no API calls)
- **REST API Connectivity Tests** - Test API connection and status endpoints
- **REST API VAT Validation Tests** - Test actual VAT validation with API calls

### VatValidatorSoapFunctionalTest.php

- **Configuration Tests** - Verify SOAP client is configured
- **Format Validation Tests** - Test VAT format validation (local, no API calls)
- **SOAP API VAT Existence Tests** - Test VAT existence validation with API calls
- **Full Validation Tests** - Test combined format + existence checks

## Code Coverage

Generate HTML code coverage report:

```bash
composer test-coverage
```

The report will be generated in the `coverage/` directory.

## Running All Tests

To run both unit and functional tests:

```bash
vendor/bin/phpunit
```

## Additional Testing Commands

```bash
# Run tests with specific filter
vendor/bin/phpunit --filter testFullValidationWithValidVatUsingRestApiCall

# Run tests by group
vendor/bin/phpunit --group functional

# List available test suites
vendor/bin/phpunit --list-suites

# List all tests
vendor/bin/phpunit --list-tests
```

## Continuous Integration

For CI/CD pipelines, you can run unit tests without the functional tests:

```bash
composer test  # Only runs unit tests
```

Or run all tests including functional:

```bash
composer test-functional  # Runs functional tests with real API calls
```

Make sure to configure the appropriate environment variables in your CI/CD system for functional tests.

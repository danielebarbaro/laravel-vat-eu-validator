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
Functional tests make actual API calls to VIES services to validate VAT numbers.

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

Functional tests use environment variables defined in `phpunit.xml.dist`:

```xml
<php>
    <env name="VIES_API_BASE_URL" value="https://viesapi.eu/api-test"/>
    <env name="VIES_API_KEY_ID" value="test_id"/>
    <env name="VIES_API_KEY" value="test_key"/>
</php>
```

### Customizing Test Environment

To use different credentials or endpoints:

**Option 1: Create a local phpunit.xml** (recommended for development)
```bash
cp phpunit.xml.dist phpunit.xml
# Edit phpunit.xml and modify the <php> section
```

**Option 2: Use environment variables**
```bash
VIES_API_BASE_URL=https://viesapi.eu/api \
VIES_API_KEY_ID=your_key_id \
VIES_API_KEY=your_key \
vendor/bin/phpunit --testsuite=functional
```

## VIES Test API Limitations

The VIES test API (`https://viesapi.eu/api-test`) works exactly like the production API but **only accepts queries for specific test VAT numbers**.

### Valid Test VAT Numbers

The test API can only validate these predefined EU VAT numbers:

| Country | VAT Numbers |
|---------|-------------|
| **Austria** | ATU74581419 |
| **Belgium** | BE0835221567 |
| **Bulgaria** | BG202211464 |
| **Croatia** | HR79147056526 |
| **Cyprus** | CY10137629O |
| **Czech Republic** | CZ7710043187 |
| **Denmark** | DK56314210 |
| **Estonia** | EE100110874 |
| **Finland** | FI23064613 |
| **France** | FR10402571889 |
| **Germany** | DE327990207 |
| **Greece** | EL801116623 |
| **Hungary** | HU29312757 |
| **Ireland** | IE8251135U |
| **Italy** | IT06903461215 |
| **Latvia** | LV40203202898 |
| **Lithuania** | LT100005828314 |
| **Luxembourg** | LU22108711 |
| **Malta** | MT26572515 |
| **Netherlands** | NL863726392B01 |
| **Poland** | PL7272445205, PL5213003700, PL5252242171, PL7171642051 |
| **Portugal** | PT501613897 |
| **Romania** | RO14388698 |
| **Slovakia** | SK2022210311 |
| **Slovenia** | SI51510847 |
| **Spain** | ES38076731R |
| **Sweden** | SE556037867001 |

For more information: https://viesapi.eu/test-vies-api/

## Testing with Production API

To test with real VAT numbers using the production VIES API:

1. **Create an account** at https://viesapi.eu
2. **Obtain API credentials** (API Key ID and API Key)
3. **Configure environment variables** in `phpunit.xml`:

```xml
<php>
    <env name="VIES_API_BASE_URL" value="https://viesapi.eu/api"/>
    <env name="VIES_API_KEY_ID" value="your_real_key_id"/>
    <env name="VIES_API_KEY" value="your_real_api_key"/>
</php>
```

Or use environment variables:
```bash
VIES_API_BASE_URL=https://viesapi.eu/api \
VIES_API_KEY_ID=your_real_key_id \
VIES_API_KEY=your_real_api_key \
composer test-functional
```

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

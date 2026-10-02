## Laravel VAT EU Validator

This package validates EU VAT numbers. It checks the format locally (regex,
plus a checksum for IT, HU and FR) and the existence against VIES, the
European Commission service. Use its rules and facade instead of writing VAT
regexes or calling VIES by hand.

### Validation rules

Three rules, available as string names or as rule objects:

- `vat_number` / `Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber`: format and VIES existence.
- `vat_number_format` / `Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberFormat`: format only, no network call.
- `vat_number_exist` / `Danielebarbaro\LaravelVatEuValidator\Rules\VatNumberExist`: format, then VIES existence.

@verbatim
<code-snippet name="Validate a VAT number in a request" lang="php">
use Danielebarbaro\LaravelVatEuValidator\Rules\VatNumber;

$request->validate([
    'vat' => ['nullable', 'bail', 'string', new VatNumber()],
    'vat_draft' => ['nullable', 'bail', 'string', 'vat_number_format'],
]);
</code-snippet>
@endverbatim

The value must carry the two letter country prefix (`IT00743110157`). Greece
uses `EL`, Northern Ireland `XI`; `GB` and `CH` are accepted by the format
check. Leading and trailing spaces are trimmed and the value is uppercased,
inner spaces and dots are not removed, so normalise user input first.

The rules pass the value straight to a `string` parameter: `null` or an array
throws a `TypeError` instead of failing validation. Keep `nullable` and
`bail` plus `string` in front of them, as above.

Messages live under `laravelVatEuValidator::validation.vat_number` (and
`.vat_number_format`, `.vat_number_exist`). Publish them with
`php artisan vendor:publish --tag=laravel-vat-eu-validator-lang`.

### Facade and service

@verbatim
<code-snippet name="Validate outside a form request" lang="php">
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;

VatValidator::validateFormat('IT00743110157');    // bool, never calls VIES
VatValidator::validateExistence('IT00743110157'); // bool, calls VIES when the format is valid
VatValidator::validate('IT00743110157');          // bool, both
VatValidator::countryIsSupported('IT');           // bool
</code-snippet>
@endverbatim

The facade resolves `Danielebarbaro\LaravelVatEuValidator\VatValidator`, bound
as a singleton. It can be type hinted instead of using the facade.

### When VIES fails

There is no caching and no retry. When VIES cannot be reached, times out or
answers with a fault, `validate()` and `validateExistence()` throw
`Danielebarbaro\LaravelVatEuValidator\Vies\ViesException`, and so do the
`vat_number` and `vat_number_exist` rules: the exception escapes the
validator, it is not turned into a validation error. Catch it where an
outage must not block the user, for example fall back to
`VatValidator::validateFormat()` and check again later. Cache results in the
application if needed.

With the REST client, a successful response whose `valid` field is false
returns false, whatever the reason VIES gives.

### Choosing the VIES client

Publish the config with
`php artisan vendor:publish --tag=laravel-vat-eu-validator-config`, then edit
`config/vat-validator.php`:

- `vat-validator.client`: `soap` (default, `ViesSoapClient::CLIENT_NAME`) or `rest` (`ViesRestClient::CLIENT_NAME`). Any other value throws `InvalidArgumentException` when the validator is resolved.
- `vat-validator.clients.soap.timeout`: seconds, passed to `SoapClient` as `connection_timeout`, so it limits the connection only. Needs `ext-soap`.
- `vat-validator.clients.rest.timeout`: seconds, the timeout of the whole HTTP request.
- `vat-validator.clients.rest.base_url`: defaults to `ViesRestClient::BASE_URL`, overridable with the `VIES_REST_BASE_URL` env variable.

@verbatim
<code-snippet name="Switch to the REST client" lang="php">
use Danielebarbaro\LaravelVatEuValidator\Vies\ViesRestClient;

'client' => ViesRestClient::CLIENT_NAME,
</code-snippet>
@endverbatim

`ViesRestClient::getViesStatus()` returns the VIES status and the availability
of each member state.

### Testing code that uses the package

Never hit VIES from tests. Mock the facade, which the rules also use, or bind
a fake `Danielebarbaro\LaravelVatEuValidator\Vies\ViesClientInterface` before
the validator is first resolved. With the REST client, `Http::fake()` works too.

@verbatim
<code-snippet name="Fake the VIES result" lang="php">
use Danielebarbaro\LaravelVatEuValidator\Facades\VatValidatorFacade as VatValidator;

VatValidator::shouldReceive('validate')->andReturn(true);
</code-snippet>
@endverbatim

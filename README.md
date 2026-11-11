# Laravel CRO Open Services

[![Tests](https://github.com/codeitamarjr/laravel-cro-open-services/actions/workflows/tests.yml/badge.svg)](https://github.com/codeitamarjr/laravel-cro-open-services/actions/workflows/tests.yml)

A small Laravel SDK for Ireland's [CRO Open Services](https://services.cro.ie/) API, the Companies Registration Office REST API for company and submission data.

It gives you an HTTP client, a container binding and a facade for the public endpoints under `/cws`.

## Requirements

- PHP 8.1+ (CI tests 8.2 to 8.5)
- Laravel 10, 11, 12 or 13
- CRO Open Services credentials (an email and API key from https://services.cro.ie/)

## Install

The package is not on Packagist yet, so add the GitHub repository to your app's `composer.json` first:

```json
"repositories": {
    "laravel-cro-open-services": {
        "type": "vcs",
        "url": "https://github.com/codeitamarjr/laravel-cro-open-services"
    }
}
```

Then require it:

```bash
composer require codeitamarjr/laravel-cro-open-services
```

The service provider and the `CroOpenServices` facade are registered automatically.

## Configuration

Add your credentials to `.env`:

```dotenv
CRO_EMAIL=you@example.com
CRO_API_KEY=your-key
```

Each setting checks these variables in order and uses the first one that is set:

| Config key | Env variables |
| --- | --- |
| `cro-open-services.email` | `CRO_OPEN_SERVICES_EMAIL`, `CRO_API_EMAIL`, `CRO_EMAIL` |
| `cro-open-services.key` | `CRO_OPEN_SERVICES_KEY`, `CRO_API_KEY` |

To change how the config reads them, publish the config file:

```bash
php artisan vendor:publish --tag=cro-open-services-config
```

The client does not check the credentials for you. Check them yourself before calling the API:

```php
$configured = filled(config('cro-open-services.email')) && filled(config('cro-open-services.key'));
```

## Usage

Inject the client or use the facade:

```php
use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesClient;
use Codeitamarjr\LaravelCroOpenServices\Facades\CroOpenServices;

public function show(CroOpenServicesClient $cro)
{
    $companies = $cro->searchCompaniesByNumber('83740');
    $matches = $cro->searchCompaniesByName('ryanair');
    $count = $cro->getCompanyCount(['company_name' => 'smith']);
}

$company = CroOpenServices::getCompany('83740');
$submissions = CroOpenServices::getCompanySubmissions('83740');
$submission = CroOpenServices::getSubmission('6191121', '2');
```

## Methods

| Method | Endpoint | Returns |
| --- | --- | --- |
| `searchCompaniesByNumber($number, $busInd = 'C')` | `GET /companies` | list of companies |
| `searchCompaniesByName($name, $busInd = 'C', $searchType = 2, $skip = 0, $max = 25)` | `GET /companies` | list of companies |
| `searchCompanies(array $params)` | `GET /companies` | list of companies |
| `getCompanyCount(array $params)` | `GET /companycount` | `int` |
| `getCompany($number, $busInd = 'c')` | `GET /company/{number}/{busInd}` | company details |
| `getCompanySubmissions($number, $busInd = 'c')` | `GET /company/{number}/{busInd}/submissions` | list of submissions |
| `searchSubmissions(array $params)` | `GET /submissions` | list of submissions |
| `getSubmissionCount(array $params)` | `GET /submissioncount` | `int` |
| `getSubmission($subNum, $docNum)` | `GET /submission/{subNum}/{docNum}` | submission document |
| `searchSubmissionsByCompanyNumber($number, $busInd = 'C')` | `GET /submissions` (all pages) | **latest submission of each type only** |
| `getDataDictionary()` | none | local `Company` / `SubmissionDoc` field map |

Notes:

- `$busInd` is the company/business indicator: `C` for a company, `B` for a business name. It is case-insensitive.
- `format=json` is added to every request. Parameters with `null` or empty-string values are left out.
- Any other CRO query parameter (`skip`, `max`, `sortBy`, `htmlEnc`, …) can be passed in `$params`.
- An empty or non-JSON success response is returned as `[]` (or `0` for the count methods).

## Error handling

Every method calls `->throw()`, so any non-2xx response from CRO (bad credentials, not found, server error) throws `Illuminate\Http\Client\RequestException`. Connection failures and timeouts throw `Illuminate\Http\Client\ConnectionException`. Catch them wherever a CRO outage shouldn't break your page or job:

```php
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

try {
    $company = CroOpenServices::getCompany($number);
} catch (RequestException|ConnectionException $exception) {
    report($exception);

    $company = null;
}
```

Requests time out after 15 seconds (5 seconds to connect).

### Rate limits

CRO is behind Cloudflare, which rate-limits with HTTP `429` or the body `error code: 1015`.

`searchSubmissionsByCompanyNumber()` handles this for you. It logs a warning, sleeps 10 seconds and retries, up to 3 times per page, then throws. It also waits 750 ms between pages. The other methods don't retry, so a rate-limited request throws straight away.

## Testing your app

The client uses Laravel's HTTP client, so fake it in your tests with `Http::fake()`:

```php
Http::fake([
    'https://services.cro.ie/cws/company/83740/c*' => Http::response(['company_num' => 83740, 'company_name' => 'FOSTER WHEELER IRELAND LIMITED']),
]);
```

## Developing the package

```bash
composer install
composer test
```

To run the live smoke test against the real API:

```bash
export CRO_EMAIL=you@example.com
export CRO_API_KEY=your-key
composer test:live
```

Without exported credentials, the live test uses CRO's public test credentials. It checks the documented examples (company `83740`, name search `ryanair`, counts, submission search and one submission document).

## Scope

This package only covers CRO Open Services. It doesn't scrape CORE pages or download paid documents.

## License

MIT

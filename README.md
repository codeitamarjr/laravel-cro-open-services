# Laravel CRO API

A minimal Laravel wrapper for the Irish Company Registration Office (CRO) API. It provides a fluent client, container binding, and a facade for common CRO lookups.

## Install

```bash
composer require codeitamarjr/laravel-cro-api
```

Publish the config if you need to override defaults:

```bash
php artisan vendor:publish --tag=cro-api-config
```

## Configuration

Set your credentials (and optionally override the base URL/timeouts) in your `.env`:

```
CRO_API_EMAIL=you@example.com
CRO_API_KEY=your-key
CRO_API_BASE_URL=https://services.cro.ie/cws
CRO_API_HTTP_TIMEOUT=15
CRO_API_MAX_PER_PAGE=100
CRO_API_RATE_LIMIT_SLEEP_SECONDS=10
CRO_API_DELAY_BETWEEN_REQUESTS_MS=750
```

`CRO_EMAIL` will also be read if `CRO_API_EMAIL` is not set, keeping compatibility with existing env names.

## Usage

Resolve the client out of the container (or use the `CroApi` facade):

```php
use Codeitamarjr\LaravelCroApi\CroApiClient;
use Codeitamarjr\LaravelCroApi\Facades\CroApi;

// Via dependency injection
public function show(CroApiClient $cro)
{
    $companies = $cro->searchByNumber('123456');
}

// Via facade
$details = CroApi::getCompanyDetails('123456');
$submissions = CroApi::getCompanySubmissions('123456');
$latestByType = CroApi::searchCompanySubmissions('123456'); // paginated + deduped
```

### Available methods

- `searchByNumber(string $number): array` — Filter companies by number.
- `getCompanyDetails(string $number): array` — Fetch company profile data.
- `getCompanySubmissions(string $number): array` — Retrieve submissions for one company.
- `searchCompanySubmissions(string $number, string $busIndicator = 'c'): array` — Paginate through submissions, handling Cloudflare rate limits and returning the latest submission per type.

## Testing locally

When working in a single repo, you can point Composer to the path:

```bash
composer config repositories.laravel-cro-api path ./packages/laravel-cro-api
composer require codeitamarjr/laravel-cro-api:*
```

The package is auto-discovered by Laravel; no manual provider registration is needed.

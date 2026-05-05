# Laravel CRO Open Services

[![Tests](https://github.com/codeitamarjr/laravel-cro-open-services/actions/workflows/tests.yml/badge.svg)](https://github.com/codeitamarjr/laravel-cro-open-services/actions/workflows/tests.yml)

A small Laravel SDK for Ireland's CRO Open Services API.

CRO Open Services is the Companies Registration Office REST API for integrating company and submission data into applications. This package exposes a Laravel HTTP client, service container binding, and facade for the public Open Services endpoints under `/cws`.

Official service: https://services.cro.ie/

## Install

```bash
composer require codeitamarjr/laravel-cro-open-services
```

Publish the config if you need to override defaults:

```bash
php artisan vendor:publish --tag=cro-open-services-config
```

## Configuration

Set your CRO Open Services credentials in `.env`:

```dotenv
CRO_EMAIL=you@example.com
CRO_API_KEY=your-key
```

More explicit `CRO_OPEN_SERVICES_EMAIL` and `CRO_OPEN_SERVICES_KEY` variables are also supported if you want to separate this SDK from older app configuration later.

## Usage

```php
use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesClient;
use Codeitamarjr\LaravelCroOpenServices\Facades\CroOpenServices;

public function show(CroOpenServicesClient $cro)
{
    $companies = $cro->searchCompaniesByNumber('123456');
}

$company = CroOpenServices::getCompany('123456');
$submissions = CroOpenServices::getCompanySubmissions('123456');
$latestByType = CroOpenServices::searchSubmissionsByCompanyNumber('123456');
```

## Available methods

- `searchCompaniesByNumber(string $companyNumber, string $companyBusIndicator = 'C'): array`
- `getCompany(string $companyNumber, string $companyBusIndicator = 'c'): array`
- `getCompanySubmissions(string $companyNumber, string $companyBusIndicator = 'c'): array`
- `searchSubmissionsByCompanyNumber(string $companyNumber, string $companyBusIndicator = 'C'): array`

Endpoint coverage:

- `GET /companies`
- `GET /company/{companyNumber}/{companyBusIndicator}`
- `GET /company/{companyNumber}/{companyBusIndicator}/submissions`
- `GET /submissions`

## Scope

This package targets CRO Open Services. It does not attempt to wrap unrelated Irish company APIs, screen-scrape CORE pages, or provide paid document-streaming helpers until those endpoints are explicitly modelled and tested.

## Local development

```bash
composer install
composer test
```

Run the live CRO Open Services smoke test only when credentials are available:

```bash
export CRO_EMAIL=you@example.com
export CRO_API_KEY=your-key
composer test:live
```

If no credentials are exported, the live test uses CRO's official public test credential pair. It checks company number `83740` with company/business indicator `C` and verifies that `/company/83740/c` returns the documented `FOSTER WHEELER IRELAND LIMITED` payload shape.

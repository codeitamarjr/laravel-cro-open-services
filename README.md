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
    $matches = $cro->searchCompaniesByName('ryanair');
    $count = $cro->getCompanyCount(['company_name' => 'smith']);
}

$company = CroOpenServices::getCompany('123456');
$submission = CroOpenServices::getSubmission('6191121', '2');
$submissions = CroOpenServices::getCompanySubmissions('123456');
$submissionCount = CroOpenServices::getSubmissionCount(['company_num' => '54512', 'company_bus_ind' => 'c']);
$latestByType = CroOpenServices::searchSubmissionsByCompanyNumber('123456');
$dictionary = CroOpenServices::getDataDictionary();
```

## Available methods

- `searchCompaniesByNumber(string $companyNumber, string $companyBusIndicator = 'C'): array`
- `searchCompaniesByName(string $companyName, string $companyBusIndicator = 'C', int $searchType = 2, int $skip = 0, int $max = 25): array`
- `searchCompanies(array $parameters): array`
- `getCompanyCount(array $parameters): int`
- `getCompany(string $companyNumber, string $companyBusIndicator = 'c'): array`
- `getCompanySubmissions(string $companyNumber, string $companyBusIndicator = 'c'): array`
- `searchSubmissions(array $parameters): array`
- `getSubmissionCount(array $parameters): int`
- `getSubmission(string $submissionNumber, string $documentNumber): array`
- `searchSubmissionsByCompanyNumber(string $companyNumber, string $companyBusIndicator = 'C'): array`
- `getDataDictionary(): array`

Endpoint coverage:

- `GET /companies`
- `GET /companycount`
- `GET /company/{companyNumber}/{companyBusIndicator}`
- `GET /company/{companyNumber}/{companyBusIndicator}/submissions`
- `GET /submissions`
- `GET /submissioncount`
- `GET /submission/{submissionNumber}/{documentNumber}`

The data dictionary is exposed as a local `Company` and `SubmissionDoc` field map based on CRO's published data dictionary.

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

If no credentials are exported, the live test uses CRO's official public test credential pair. It checks company number `83740` with company/business indicator `C`, verifies that `/company/83740/c` returns the documented `FOSTER WHEELER IRELAND LIMITED` payload shape, verifies numeric `/companies` search for `83740`, verifies name `/companies` search for `ryanair`, and exercises CRO's documented examples for company count, submission search, submission count, and one submission document.

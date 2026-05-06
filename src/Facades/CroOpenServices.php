<?php

namespace Codeitamarjr\LaravelCroOpenServices\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array searchCompaniesByNumber(string $companyNumber, string $companyBusIndicator = 'C')
 * @method static array searchCompaniesByName(string $companyName, string $companyBusIndicator = 'C', int $searchType = 2, int $skip = 0, int $max = 25)
 * @method static array getCompany(string $companyNumber, string $companyBusIndicator = 'c')
 * @method static array getCompanySubmissions(string $companyNumber, string $companyBusIndicator = 'c')
 * @method static array searchSubmissionsByCompanyNumber(string $companyNumber, string $companyBusIndicator = 'C')
 */
class CroOpenServices extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cro-open-services';
    }
}

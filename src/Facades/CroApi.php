<?php

namespace Codeitamarjr\LaravelCroApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array searchByNumber(string $number)
 * @method static array getCompanyDetails(string $number)
 * @method static array getCompanySubmissions(string $number)
 * @method static array searchCompanySubmissions(string $companyNumber, string $busIndicator = 'c')
 */
class CroApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cro-api';
    }
}

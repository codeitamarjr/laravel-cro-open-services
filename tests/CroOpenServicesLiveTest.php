<?php

namespace Codeitamarjr\LaravelCroOpenServices\Tests;

use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesClient;
use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Group;

class CroOpenServicesLiveTest extends TestCase
{
    private const OFFICIAL_TEST_EMAIL = 'test@cro.ie';

    private const OFFICIAL_TEST_KEY = 'da093a04-c9d7-46d7-9c83-9c9f8630d5e0';

    private const OFFICIAL_TEST_COMPANY_NUMBER = '83740';

    private const OFFICIAL_TEST_COMPANY_BUS_INDICATOR = 'C';

    protected function getPackageProviders($app): array
    {
        return [CroOpenServicesServiceProvider::class];
    }

    #[Group('live')]
    public function test_can_fetch_company_details_from_cro_open_services(): void
    {
        if (getenv('CRO_OPEN_SERVICES_LIVE_TESTS') !== '1') {
            $this->markTestSkipped('Set CRO_OPEN_SERVICES_LIVE_TESTS=1 to run live CRO Open Services tests.');
        }

        $email = getenv('CRO_OPEN_SERVICES_EMAIL') ?: getenv('CRO_API_EMAIL') ?: getenv('CRO_EMAIL') ?: self::OFFICIAL_TEST_EMAIL;
        $key = getenv('CRO_OPEN_SERVICES_KEY') ?: getenv('CRO_API_KEY') ?: self::OFFICIAL_TEST_KEY;

        $client = new CroOpenServicesClient(
            email: $email,
            key: $key,
            httpTimeout: 15,
            connectTimeout: 5,
        );

        $company = $client->getCompany(
            self::OFFICIAL_TEST_COMPANY_NUMBER,
            self::OFFICIAL_TEST_COMPANY_BUS_INDICATOR,
        );

        $this->assertSame(83740, $company['company_num'] ?? null);
        $this->assertSame('c', $company['company_bus_ind'] ?? null);
        $this->assertSame('FOSTER WHEELER IRELAND LIMITED', $company['company_name'] ?? null);
        $this->assertSame('Dissolved', $company['company_status_desc'] ?? null);
        $this->assertSame('Private limited by shares', $company['comp_type_desc'] ?? null);
    }
}

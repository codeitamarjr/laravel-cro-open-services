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

    private const OFFICIAL_TEST_COMPANY_NAME_QUERY = 'ryanair';

    protected function getPackageProviders($app): array
    {
        return [CroOpenServicesServiceProvider::class];
    }

    #[Group('live')]
    public function test_can_fetch_company_details_from_cro_open_services(): void
    {
        $client = $this->liveClient();

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

    #[Group('live')]
    public function test_can_search_companies_by_number_from_cro_open_services(): void
    {
        $client = $this->liveClient();

        $companies = $client->searchCompaniesByNumber(
            self::OFFICIAL_TEST_COMPANY_NUMBER,
            self::OFFICIAL_TEST_COMPANY_BUS_INDICATOR,
        );

        $this->assertNotEmpty($companies);

        $firstMatch = $companies[0] ?? [];

        $this->assertSame(83740, $firstMatch['company_num'] ?? null);
        $this->assertSame('C', strtoupper((string) ($firstMatch['company_bus_ind'] ?? '')));
        $this->assertSame('FOSTER WHEELER IRELAND LIMITED', $firstMatch['company_name'] ?? null);
    }

    #[Group('live')]
    public function test_can_search_companies_by_name_from_cro_open_services(): void
    {
        $client = $this->liveClient();

        $companies = $client->searchCompaniesByName(
            self::OFFICIAL_TEST_COMPANY_NAME_QUERY,
            self::OFFICIAL_TEST_COMPANY_BUS_INDICATOR,
            searchType: 3,
            max: 10,
        );

        $this->assertNotEmpty($companies);

        $names = collect($companies)
            ->pluck('company_name')
            ->filter()
            ->map(fn (string $name) => strtoupper($name));

        $this->assertTrue(
            $names->contains(fn (string $name) => str_contains($name, 'RYANAIR')),
            'Expected at least one CRO company search result containing RYANAIR.',
        );
    }

    private function liveClient(): CroOpenServicesClient
    {
        if (getenv('CRO_OPEN_SERVICES_LIVE_TESTS') !== '1') {
            $this->markTestSkipped('Set CRO_OPEN_SERVICES_LIVE_TESTS=1 to run live CRO Open Services tests.');
        }

        $email = getenv('CRO_OPEN_SERVICES_EMAIL') ?: getenv('CRO_API_EMAIL') ?: getenv('CRO_EMAIL') ?: self::OFFICIAL_TEST_EMAIL;
        $key = getenv('CRO_OPEN_SERVICES_KEY') ?: getenv('CRO_API_KEY') ?: self::OFFICIAL_TEST_KEY;

        return new CroOpenServicesClient(
            email: $email,
            key: $key,
            httpTimeout: 15,
            connectTimeout: 5,
        );
    }
}

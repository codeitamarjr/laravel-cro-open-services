<?php

namespace Codeitamarjr\LaravelCroOpenServices\Tests;

use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesClient;
use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class CroOpenServicesClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    protected function getPackageProviders($app): array
    {
        return [CroOpenServicesServiceProvider::class];
    }

    public function test_search_companies_by_number_sends_open_services_request(): void
    {
        Http::fake([
            'https://cro.test/companies*' => Http::response([['company_num' => '123456']], 200),
        ]);

        $client = new CroOpenServicesClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            connectTimeout: 1,
            maxPerPage: 2,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );

        $result = $client->searchCompaniesByNumber('123456');

        $this->assertSame([['company_num' => '123456']], $result);

        Http::assertSent(function (Request $request) {
            $this->assertStringStartsWith('https://cro.test/companies', $request->url());
            $this->assertSame('C', $request['company_bus_ind']);
            $this->assertSame('123456', $request['company_num']);
            $this->assertSame('application/json', $request->header('Accept')[0] ?? null);
            $this->assertSame(
                'Basic '.base64_encode('user@example.com:secret-key'),
                $request->header('Authorization')[0] ?? null
            );

            return true;
        });
    }

    public function test_get_company_returns_payload(): void
    {
        Http::fake([
            'https://cro.test/company/123456/c*' => Http::response(['name' => 'ACME'], 200),
        ]);

        $client = $this->makeClient();

        $this->assertSame(['name' => 'ACME'], $client->getCompany('123456'));
    }

    public function test_get_company_matches_official_testing_example(): void
    {
        $officialPayload = [
            'company_num' => 83740,
            'company_bus_ind' => 'c',
            'company_name' => 'FOSTER WHEELER IRELAND LIMITED',
            'company_addr_1' => 'C/O COOPERS LYBRAND',
            'company_addr_2' => 'FITZWILTON HOUSE',
            'company_addr_3' => 'WILTON PLACE DUBLIN 2.',
            'company_addr_4' => 'DUBLIN, DUBLIN, Ireland',
            'company_reg_date' => '1981-07-08T00:00:00Z',
            'company_status_desc' => 'Dissolved',
            'company_status_date' => '1993-11-19T00:00:00Z',
            'last_ar_date' => '1988-12-31T00:00:00Z',
            'next_ar_date' => '2003-12-31T00:00:00Z',
            'last_acc_date' => '0001-01-01T00:00:00Z',
            'comp_type_desc' => 'Private limited by shares',
            'company_type_code' => 1119,
            'company_status_code' => 1158,
            'place_of_business' => 'Ireland',
            'eircode' => '',
        ];

        Http::fake([
            'https://cro.test/company/83740/c*' => Http::response($officialPayload, 200),
        ]);

        $client = $this->makeClient();

        $this->assertSame($officialPayload, $client->getCompany('83740', 'C'));

        Http::assertSent(function (Request $request) {
            $this->assertStringStartsWith('https://cro.test/company/83740/c', $request->url());
            $this->assertSame('json', $request['format']);

            return true;
        });
    }

    public function test_get_company_submissions_returns_payload(): void
    {
        Http::fake([
            'https://cro.test/company/123456/c/submissions*' => Http::response([['sub' => 1]], 200),
        ]);

        $client = $this->makeClient();

        $this->assertSame([['sub' => 1]], $client->getCompanySubmissions('123456'));
    }

    public function test_search_submissions_by_company_number_handles_rate_limit_and_deduplicates(): void
    {
        Log::shouldReceive('warning')->once();

        Http::fakeSequence()
            ->push('error code: 1015', 200)
            ->push([
                ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-05-02'],
                ['sub_type_desc' => 'B1', 'sub_received_date' => '2024-05-01'],
            ], 200)
            ->push([
                ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-01-01'],
            ], 200);

        $client = new CroOpenServicesClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            connectTimeout: 1,
            maxPerPage: 2,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );

        $results = $client->searchSubmissionsByCompanyNumber('123456');

        $this->assertSame([
            ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-05-02'],
            ['sub_type_desc' => 'B1', 'sub_received_date' => '2024-05-01'],
        ], $results);

        Http::assertSentCount(3);
        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://cro.test/submissions')
                && $request['company_bus_ind'] === 'C'
                && $request['company_num'] === '123456'
                && $request['skip'] === 0
                && $request['max'] === 2
                && $request['format'] === 'json';
        });
        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://cro.test/submissions')
                && $request['skip'] === 2;
        });
    }

    private function makeClient(): CroOpenServicesClient
    {
        return new CroOpenServicesClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            connectTimeout: 1,
            maxPerPage: 100,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );
    }
}

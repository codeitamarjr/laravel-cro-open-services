<?php

namespace Codeitamarjr\LaravelCroApi\Tests;

use Codeitamarjr\LaravelCroApi\CroApiClient;
use Codeitamarjr\LaravelCroApi\CroApiServiceProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class CroApiClientTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [CroApiServiceProvider::class];
    }

    public function test_search_by_number_sends_auth_headers(): void
    {
        Http::fake([
            'https://cro.test/companies*' => Http::response([['company_num' => '123456']], 200),
        ]);

        $client = new CroApiClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            maxPerPage: 2,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );

        $result = $client->searchByNumber('123456');

        $this->assertSame([['company_num' => '123456']], $result);

        Http::assertSent(function (Request $request) {
            $this->assertStringStartsWith('https://cro.test/companies', $request->url());
            $this->assertSame('C', $request['company_bus_ind']);
            $this->assertSame('123456', $request['company_num']);
            $this->assertSame('application/json', $request->header('Accept')[0] ?? null);
            $this->assertSame(
                'Basic ' . base64_encode('user@example.com:secret-key'),
                $request->header('Authorization')[0] ?? null
            );

            return true;
        });
    }

    public function test_get_company_details_returns_payload(): void
    {
        Http::fake([
            'https://cro.test/company/123456/c*' => Http::response(['name' => 'ACME'], 200),
        ]);

        $client = $this->makeClient();

        $this->assertSame(['name' => 'ACME'], $client->getCompanyDetails('123456'));
    }

    public function test_get_company_submissions_returns_payload(): void
    {
        Http::fake([
            'https://cro.test/submissions/123456/c*' => Http::response([['sub' => 1]], 200),
        ]);

        $client = $this->makeClient();

        $this->assertSame([['sub' => 1]], $client->getCompanySubmissions('123456'));
    }

    public function test_search_company_submissions_handles_rate_limit_and_deduplicates(): void
    {
        Log::shouldReceive('warning')->once();

        Http::fakeSequence()
            ->push('error code: 1015', 200) // triggers retry
            ->push([
                ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-05-02'],
                ['sub_type_desc' => 'B1', 'sub_received_date' => '2024-05-01'],
            ], 200)
            ->push([
                ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-01-01'], // older duplicate, should be dropped
            ], 200);

        $client = new CroApiClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            maxPerPage: 2,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );

        $results = $client->searchCompanySubmissions('123456');

        $this->assertSame([
            ['sub_type_desc' => 'A1', 'sub_received_date' => '2024-05-02'],
            ['sub_type_desc' => 'B1', 'sub_received_date' => '2024-05-01'],
        ], $results);

        Http::assertSentCount(3);
    }

    private function makeClient(): CroApiClient
    {
        return new CroApiClient(
            email: 'user@example.com',
            key: 'secret-key',
            baseUrl: 'https://cro.test',
            httpTimeout: 5,
            maxPerPage: 100,
            rateLimitSleepSeconds: 0,
            delayBetweenRequestsMs: 0,
        );
    }
}

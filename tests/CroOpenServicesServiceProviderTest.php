<?php

namespace Codeitamarjr\LaravelCroOpenServices\Tests;

use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesClient;
use Codeitamarjr\LaravelCroOpenServices\CroOpenServicesServiceProvider;
use Codeitamarjr\LaravelCroOpenServices\Facades\CroOpenServices;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class CroOpenServicesServiceProviderTest extends TestCase
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

    public function test_it_binds_the_client_and_facade_with_configured_credentials(): void
    {
        config()->set('cro-open-services.email', 'configured@example.com');
        config()->set('cro-open-services.key', 'configured-key');

        Http::fake([
            'https://services.cro.ie/cws/company/83740/c*' => Http::response([
                'company_num' => 83740,
                'company_name' => 'FOSTER WHEELER IRELAND LIMITED',
            ], 200),
        ]);

        $client = $this->app->make(CroOpenServicesClient::class);

        $this->assertSame($client, $this->app->make('cro-open-services'));
        $this->assertSame([
            'company_num' => 83740,
            'company_name' => 'FOSTER WHEELER IRELAND LIMITED',
        ], CroOpenServices::getCompany('83740'));

        Http::assertSent(function (Request $request) {
            $this->assertSame(
                'Basic '.base64_encode('configured@example.com:configured-key'),
                $request->header('Authorization')[0] ?? null,
            );

            return str_starts_with($request->url(), 'https://services.cro.ie/cws/company/83740/c');
        });
    }
}

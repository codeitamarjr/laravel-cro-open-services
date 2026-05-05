<?php

namespace Codeitamarjr\LaravelCroOpenServices;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CroOpenServicesClient
{
    public function __construct(
        protected string $email,
        protected string $key,
        protected string $baseUrl = 'https://services.cro.ie/cws',
        protected int $httpTimeout = 15,
        protected int $connectTimeout = 5,
        protected int $maxPerPage = 100,
        protected int $rateLimitSleepSeconds = 10,
        protected int $delayBetweenRequestsMs = 750
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Search CRO Open Services companies by company number.
     *
     * @return array<int|string, mixed>
     */
    public function searchCompaniesByNumber(string $companyNumber, string $companyBusIndicator = 'C'): array
    {
        return $this->http()
            ->get('/companies', [
                'company_num' => $companyNumber,
                'company_bus_ind' => strtoupper($companyBusIndicator),
                'format' => 'json',
            ])
            ->throw()
            ->json();
    }

    /**
     * Get CRO Open Services company details by company number.
     *
     * @return array<int|string, mixed>
     */
    public function getCompany(string $companyNumber, string $companyBusIndicator = 'c'): array
    {
        return $this->http()
            ->get("/company/{$companyNumber}/".strtolower($companyBusIndicator), [
                'format' => 'json',
            ])
            ->throw()
            ->json();
    }

    /**
     * Get submissions listed under one company.
     *
     * @return array<int|string, mixed>
     */
    public function getCompanySubmissions(string $companyNumber, string $companyBusIndicator = 'c'): array
    {
        $json = $this->http()
            ->get("/company/{$companyNumber}/".strtolower($companyBusIndicator).'/submissions', [
                'format' => 'json',
            ])
            ->throw()
            ->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Search submissions by company number and return the latest submission per type.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchSubmissionsByCompanyNumber(string $companyNumber, string $companyBusIndicator = 'C'): array
    {
        $results = [];
        $skip = 0;

        while (true) {
            $response = $this->http()
                ->get('/submissions', [
                    'company_bus_ind' => strtoupper($companyBusIndicator),
                    'company_num' => $companyNumber,
                    'skip' => $skip,
                    'max' => $this->maxPerPage,
                    'format' => 'json',
                ])
                ->throw();

            $body = trim($response->body());

            if ($body === 'error code: 1015') {
                Log::warning("CRO Open Services rate limit for {$companyNumber}. Sleeping {$this->rateLimitSleepSeconds}s and retrying.");
                sleep($this->rateLimitSleepSeconds);

                continue;
            }

            $batch = json_decode($body, true);

            if (! is_array($batch)) {
                Log::error('Invalid CRO Open Services submissions response.', ['response' => $body]);
                break;
            }

            $results = array_merge($results, $batch);
            $skip += $this->maxPerPage;

            if (count($batch) < $this->maxPerPage) {
                break;
            }

            usleep($this->delayBetweenRequestsMs * 1000);
        }

        usort($results, fn ($a, $b) => strcmp($b['sub_received_date'] ?? '', $a['sub_received_date'] ?? ''));

        $seen = [];
        $latestUnique = [];

        foreach ($results as $submission) {
            $type = $submission['sub_type_desc'] ?? null;

            if ($type && ! isset($seen[$type])) {
                $latestUnique[] = $submission;
                $seen[$type] = true;
            }
        }

        return $latestUnique;
    }

    protected function authHeader(): string
    {
        return base64_encode("{$this->email}:{$this->key}");
    }

    /**
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    protected function headers(array $extra = []): array
    {
        return array_merge([
            'Authorization' => 'Basic '.$this->authHeader(),
            'Accept' => 'application/json',
        ], $extra);
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders($this->headers())
            ->timeout($this->httpTimeout)
            ->connectTimeout($this->connectTimeout);
    }
}

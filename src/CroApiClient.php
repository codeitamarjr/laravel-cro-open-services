<?php

namespace Codeitamarjr\LaravelCroApi;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CroApiClient
{
    public function __construct(
        protected string $email,
        protected string $key,
        protected string $baseUrl = 'https://services.cro.ie/cws',
        protected int $httpTimeout = 15,
        protected int $maxPerPage = 100,
        protected int $rateLimitSleepSeconds = 10,
        protected int $delayBetweenRequestsMs = 750
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Search the CRO by company number.
     *
     * @return array<int|string,mixed>
     */
    public function searchByNumber(string $number): array
    {
        $response = $this->http()->get("{$this->baseUrl}/companies", [
            'company_num' => $number,
            'company_bus_ind' => 'C',
            'format' => 'json',
        ]);

        return $response->json();
    }

    /**
     * Lookup company details by number.
     *
     * @return array<int|string,mixed>
     */
    public function getCompanyDetails(string $number): array
    {
        $response = $this->http()->get("{$this->baseUrl}/company/{$number}/c", [
            'format' => 'json',
        ]);

        return $response->json();
    }

    /**
     * Retrieve submissions for a single company.
     *
     * @return array<int|string,mixed>
     */
    public function getCompanySubmissions(string $number): array
    {
        $response = $this->http()->get("{$this->baseUrl}/company/{$number}/c/submissions", [
            'format' => 'json',
        ]);

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Paginate through submissions keeping only the most recent of each type.
     *
     * @return array<int, array<string,mixed>>
     */
    public function searchCompanySubmissions(string $companyNumber, string $busIndicator = 'c'): array
    {
        $results = [];
        $skip = 0;

        while (true) {
            $response = $this->http()
                ->get("{$this->baseUrl}/submissions", [
                    'company_bus_ind' => strtoupper($busIndicator),
                    'company_num' => $companyNumber,
                    'skip' => $skip,
                    'max' => $this->maxPerPage,
                    'format' => 'json',
                ]);

            $body = trim($response->body());

            if ($body === 'error code: 1015') {
                Log::warning("Cloudflare 1015 rate limit for {$companyNumber}. Sleeping {$this->rateLimitSleepSeconds}s and retrying...");
                sleep($this->rateLimitSleepSeconds);
                continue;
            }

            $batch = json_decode($body, true);
            if (!is_array($batch)) {
                Log::error('Invalid CRO submission response', ['response' => $body]);
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

        foreach ($results as $doc) {
            $type = $doc['sub_type_desc'] ?? null;

            if ($type && !isset($seen[$type])) {
                $latestUnique[] = $doc;
                $seen[$type] = true;
            }
        }

        return $latestUnique;
    }

    protected function authHeader(): string
    {
        return base64_encode("{$this->email}:{$this->key}");
    }

    protected function headers(array $extra = []): array
    {
        return array_merge([
            'Authorization' => 'Basic ' . $this->authHeader(),
            'Accept' => 'application/json',
        ], $extra);
    }

    protected function http(): PendingRequest
    {
        return Http::withHeaders($this->headers())
            ->timeout($this->httpTimeout);
    }
}

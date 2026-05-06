<?php

namespace Codeitamarjr\LaravelCroOpenServices;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CroOpenServicesClient
{
    /**
     * @var array<string, array<int, array{field: string, type: string, max_length: int|null, description: string}>>
     */
    public const DATA_DICTIONARY = [
        'Company' => [
            ['field' => 'company_num', 'type' => 'Int', 'max_length' => null, 'description' => 'Company or Business number'],
            ['field' => 'company_bus_ind', 'type' => 'String', 'max_length' => 1, 'description' => 'Company Business Indicator: C indicates a Company; B indicates a Business Name'],
            ['field' => 'company_name', 'type' => 'String', 'max_length' => 200, 'description' => 'The Name of the Company or Business Name'],
            ['field' => 'company_addr_1', 'type' => 'String', 'max_length' => 800, 'description' => 'The first line of the address'],
            ['field' => 'company_addr_2', 'type' => 'String', 'max_length' => 800, 'description' => 'The second line of the address'],
            ['field' => 'company_addr_3', 'type' => 'String', 'max_length' => 800, 'description' => 'The third line of the address'],
            ['field' => 'company_addr_4', 'type' => 'String', 'max_length' => 800, 'description' => 'The fourth line of the address'],
            ['field' => 'company_reg_date', 'type' => 'String', 'max_length' => 20, 'description' => 'Company Registration Date in UTC ISO 8601 format'],
            ['field' => 'company_status_desc', 'type' => 'String', 'max_length' => 100, 'description' => 'The status of the company'],
            ['field' => 'company_status_date', 'type' => 'String', 'max_length' => 20, 'description' => 'The date on which the current status of the company was applied in UTC ISO 8601 format'],
            ['field' => 'last_ar_date', 'type' => 'String', 'max_length' => 20, 'description' => 'Last Annual Return date in UTC ISO 8601 format'],
            ['field' => 'next_ar_date', 'type' => 'String', 'max_length' => 20, 'description' => 'Next Annual Return Date in UTC ISO 8601 format'],
            ['field' => 'last_acc_date', 'type' => 'String', 'max_length' => 20, 'description' => 'Last Accounting Year Date in UTC ISO 8601 format'],
            ['field' => 'comp_type_desc', 'type' => 'String', 'max_length' => 100, 'description' => 'The type of company'],
            ['field' => 'company_type_code', 'type' => 'Int', 'max_length' => null, 'description' => "The CRO's primary key value corresponding to the company type"],
            ['field' => 'company_status_code', 'type' => 'Int', 'max_length' => null, 'description' => "The CRO's primary key value corresponding to the company status"],
            ['field' => 'place_of_business', 'type' => 'String', 'max_length' => null, 'description' => 'Where possible, the country where the original business is registered for external companies'],
            ['field' => 'eircode', 'type' => 'String', 'max_length' => 10, 'description' => 'Eircode for the registered premises when available'],
        ],
        'SubmissionDoc' => [
            ['field' => 'sub_num', 'type' => 'Int', 'max_length' => null, 'description' => 'Submission Number'],
            ['field' => 'doc_num', 'type' => 'Int', 'max_length' => null, 'description' => 'Document Number relating to the Submission'],
            ['field' => 'company_num', 'type' => 'Int', 'max_length' => null, 'description' => 'Company or Business number'],
            ['field' => 'company_bus_ind', 'type' => 'String', 'max_length' => 1, 'description' => 'Company Business Indicator: C indicates a Company; B indicates a Business Name'],
            ['field' => 'sub_type_desc', 'type' => 'String', 'max_length' => 100, 'description' => 'The type of submission'],
            ['field' => 'doc_type_desc', 'type' => 'String', 'max_length' => 100, 'description' => 'Type of document'],
            ['field' => 'sub_status_desc', 'type' => 'String', 'max_length' => 50, 'description' => 'Current status of the Submission'],
            ['field' => 'sub_received_date', 'type' => 'String', 'max_length' => 20, 'description' => 'The date on which the Submission was received in UTC ISO 8601 format'],
            ['field' => 'sub_effective_date', 'type' => 'String', 'max_length' => 20, 'description' => 'The submission effective date in UTC ISO 8601 format'],
            ['field' => 'acc_year_to_date', 'type' => 'String', 'max_length' => 20, 'description' => 'The Accounts filed up to date in UTC ISO 8601 format'],
            ['field' => 'scan_date', 'type' => 'String', 'max_length' => 20, 'description' => 'The date on which the document was last scanned in UTC ISO 8601 format'],
            ['field' => 'num_pages', 'type' => 'Int', 'max_length' => null, 'description' => 'The number of pages in the document'],
            ['field' => 'doc_id', 'type' => 'Long Int', 'max_length' => null, 'description' => 'The CRO identifier for the document, assuming it was scanned'],
            ['field' => 'file_size', 'type' => 'Int', 'max_length' => null, 'description' => 'The size of the scanned document in bytes'],
        ],
    ];

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
        return $this->searchCompanies([
            'company_num' => $companyNumber,
            'company_bus_ind' => strtoupper($companyBusIndicator),
        ]);
    }

    /**
     * Search CRO Open Services companies by company name.
     *
     * @return array<int|string, mixed>
     */
    public function searchCompaniesByName(
        string $companyName,
        string $companyBusIndicator = 'C',
        int $searchType = 2,
        int $skip = 0,
        int $max = 25
    ): array {
        return $this->searchCompanies([
            'company_name' => $companyName,
            'company_bus_ind' => strtoupper($companyBusIndicator),
            'searchType' => $searchType,
            'skip' => $skip,
            'max' => $max,
        ]);
    }

    /**
     * Search CRO Open Services companies or business names.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int|string, mixed>
     */
    public function searchCompanies(array $parameters): array
    {
        return $this->http()
            ->get('/companies', $this->jsonParameters($parameters))
            ->throw()
            ->json();
    }

    /**
     * Count CRO Open Services companies or business names for a search.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function getCompanyCount(array $parameters): int
    {
        return $this->integerResponse('/companycount', $parameters);
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
            ->get("/company/{$companyNumber}/".strtolower($companyBusIndicator).'/submissions', $this->jsonParameters())
            ->throw()
            ->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Search CRO Open Services submissions.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int|string, mixed>
     */
    public function searchSubmissions(array $parameters): array
    {
        $json = $this->http()
            ->get('/submissions', $this->jsonParameters($parameters))
            ->throw()
            ->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Count CRO Open Services submissions for a search.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function getSubmissionCount(array $parameters): int
    {
        return $this->integerResponse('/submissioncount', $parameters);
    }

    /**
     * Get CRO Open Services submission document details.
     *
     * @return array<int|string, mixed>
     */
    public function getSubmission(string $submissionNumber, string $documentNumber): array
    {
        return $this->http()
            ->get("/submission/{$submissionNumber}/{$documentNumber}", $this->jsonParameters())
            ->throw()
            ->json();
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

    /**
     * @return array<string, array<int, array{field: string, type: string, max_length: int|null, description: string}>>
     */
    public function getDataDictionary(): array
    {
        return self::DATA_DICTIONARY;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    protected function jsonParameters(array $parameters = []): array
    {
        return array_filter(
            array_merge($parameters, ['format' => 'json']),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function integerResponse(string $path, array $parameters): int
    {
        $response = $this->http()
            ->get($path, $this->jsonParameters($parameters))
            ->throw();

        $json = $response->json();

        if (is_numeric($json)) {
            return (int) $json;
        }

        return (int) trim($response->body());
    }
}

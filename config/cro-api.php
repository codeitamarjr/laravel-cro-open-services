<?php

return [
    'email' => env('CRO_API_EMAIL', env('CRO_EMAIL')),
    'key' => env('CRO_API_KEY'),
    'base_url' => env('CRO_API_BASE_URL', 'https://services.cro.ie/cws'),
    'http_timeout' => (int) env('CRO_API_HTTP_TIMEOUT', 15),
    'max_per_page' => (int) env('CRO_API_MAX_PER_PAGE', 100),
    'rate_limit_sleep_seconds' => (int) env('CRO_API_RATE_LIMIT_SLEEP_SECONDS', 10),
    'delay_between_requests_ms' => (int) env('CRO_API_DELAY_BETWEEN_REQUESTS_MS', 750),
];

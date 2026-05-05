<?php

return [
    'email' => env('CRO_OPEN_SERVICES_EMAIL', env('CRO_API_EMAIL', env('CRO_EMAIL'))),
    'key' => env('CRO_OPEN_SERVICES_KEY', env('CRO_API_KEY')),
];

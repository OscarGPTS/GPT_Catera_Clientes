<?php

return [
    'socios_email_allowlist' => env('SOCIOS_EMAIL_ALLOWLIST', ''),

    'rh' => [
        'url' => env('RH_API_URL', 'https://services.satechenergy.com/api/rh'),
        'token' => env('RH_API_TOKEN'),
        'use_mock' => env('RH_API_USE_MOCK', true),
    ],
];

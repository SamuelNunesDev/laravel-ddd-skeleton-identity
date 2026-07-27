<?php

declare(strict_types=1);

return [
    'password' => [
        'minimum_length' => (int) env('IDENTITY_PASSWORD_MIN_LENGTH', 12),
        'maximum_bytes' => (int) env('IDENTITY_PASSWORD_MAX_BYTES', 4096),
    ],

    'temporary_password' => [
        'lifetime_hours' => (int) env('IDENTITY_TEMPORARY_PASSWORD_HOURS', 24),
        'maximum_lifetime_hours' => 72,
    ],
];

<?php

declare(strict_types=1);

return [
    'time' => [
        'disk' => env('WAKTU_DISK', 'public'),
        'directory' => env('WAKTU_DIRECTORY', 'time'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
        'role_prefix' => env('WAKTU_SHIFT_ROLE_PREFIX', 'shift_code.'),
        'calendar_past_days' => 45,
        'calendar_future_days' => 120,
    ],
];

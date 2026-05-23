<?php

return [
    'daemon' => [
        'secret' => env('DAEMON_SECRET'),
        'base_path' => env('DAEMON_BASE_PATH', '/var/lib/zy4daemon'),
        'default_url' => env('DAEMON_DEFAULT_URL', 'http://127.0.0.1:7443'),
    ],
    'payments' => [
        'mode' => env('PAYMENT_MODE', 'manual'),
        'bank_name' => env('PAYMENT_BANK_NAME', 'Manual Payment'),
        'account_name' => env('PAYMENT_ACCOUNT_NAME', 'Zy4Store'),
        'account_number' => env('PAYMENT_ACCOUNT_NUMBER', '0000000000'),
    ],
    'uploads' => [
        'max_size' => env('UPLOAD_MAX_SIZE', '100M'),
    ],
];

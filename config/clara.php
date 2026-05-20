<?php

return [
    'base_url' => env('CLARA_BASE_URL'),
    'api_key' => env('CLARA_API_KEY'),
    'callback_token' => env('CLARA_CALLBACK_TOKEN'),
    'timeout_seconds' => (int) env('CLARA_TIMEOUT_SECONDS', 20),
    'cooldowns' => [
        'lead_created' => (int) env('CLARA_COOLDOWN_LEAD_CREATED_SECONDS', 600),
        'message_received' => (int) env('CLARA_COOLDOWN_MESSAGE_RECEIVED_SECONDS', 120),
        'followup_overdue' => (int) env('CLARA_COOLDOWN_FOLLOWUP_OVERDUE_SECONDS', 900),
        'status_changed' => (int) env('CLARA_COOLDOWN_STATUS_CHANGED_SECONDS', 180),
    ],
];


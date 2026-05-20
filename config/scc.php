<?php

return [
    'queue' => [
        'max_snooze_minutes' => (int) env('QUEUE_SNOOZE_MAX_MINUTES', 1440),
        'dismiss_minutes' => (int) env('QUEUE_DISMISS_MINUTES', 480),
    ],
];


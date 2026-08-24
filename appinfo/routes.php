<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'self_service#report', 'url' => '/api/v1/self-service-report', 'verb' => 'GET'],
        ['name' => 'retention_review#report', 'url' => '/api/v1/retention-review', 'verb' => 'GET'],
        ['name' => 'retention_settings#save', 'url' => '/api/v1/retention-settings', 'verb' => 'POST'],
    ],
];

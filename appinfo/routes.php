<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'self_service#report', 'url' => '/api/v1/self-service-report', 'verb' => 'GET'],
        ['name' => 'retention_review#report', 'url' => '/api/v1/retention-review', 'verb' => 'GET'],
        ['name' => 'retention_settings#save', 'url' => '/api/v1/retention-settings', 'verb' => 'POST'],
        ['name' => 'admin_history_retention_policy#show', 'url' => '/api/v1/admin-history-retention-policy', 'verb' => 'GET'],
        ['name' => 'admin_history_retention_policy#save', 'url' => '/api/v1/admin-history-retention-policy', 'verb' => 'PUT'],
        ['name' => 'admin_history_retention_policy#review', 'url' => '/api/v1/admin-history-retention-policy/review', 'verb' => 'POST'],
        ['name' => 'retention_execution_profile#show', 'url' => '/api/v1/retention-execution-profile', 'verb' => 'GET'],
        ['name' => 'retention_execution_profile#save', 'url' => '/api/v1/retention-execution-profile', 'verb' => 'PUT'],
        ['name' => 'temporary_admin_access#status', 'url' => '/api/v1/admin/full-access', 'verb' => 'GET'],
        ['name' => 'temporary_admin_access#activate', 'url' => '/api/v1/admin/full-access', 'verb' => 'POST'],
        ['name' => 'temporary_admin_access#revoke', 'url' => '/api/v1/admin/full-access/{targetUid}', 'verb' => 'DELETE'],
    ],
];

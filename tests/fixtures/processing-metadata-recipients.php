<?php

declare(strict_types=1);

// Shared cases for the app runtime validator and the Parent's canonical schema.
// The base catalog remains app-owned; only synthetic recipient values are used.
$base = json_decode(file_get_contents(dirname(__DIR__, 2) . '/resources/privacy-processing.json'), true, 512, JSON_THROW_ON_ERROR);
$recipient = ['category' => 'Synthetic recipient', 'allowed_disclosures' => ['Synthetic metadata']];
$base['processings'][0]['recipients'] = [$recipient];
$distinct = $base;
$distinct['processings'][0]['recipients'][] = ['category' => 'Synthetic reviewer', 'allowed_disclosures' => ['Synthetic review metadata']];
$duplicate = $base;
$duplicate['processings'][0]['recipients'][] = $recipient;
$reordered = $base;
$reordered['processings'][0]['recipients'][] = array_reverse($recipient, true);

return [
    'one recipient' => ['valid' => true, 'payload' => $base],
    'distinct recipients' => ['valid' => true, 'payload' => $distinct],
    'duplicate recipients' => ['valid' => false, 'payload' => $duplicate],
    'reordered duplicate recipient' => ['valid' => false, 'payload' => $reordered],
];

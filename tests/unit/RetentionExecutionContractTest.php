<?php

declare(strict_types=1);

use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionCandidate;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPage;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPolicy;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionRequest;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionResult;

$policy = new RetentionExecutionPolicy('room_booking_delete', 'Raumbuchungen', 'Fristgerechte Löschung', 'COMPLETED_AT', 'P1Y', 'DELETE', '2.3');
$candidate = new RetentionExecutionCandidate('room_booking_delete', 'booking:17', '2025-01-01T10:00:00+00:00', 'DELETE', '2.3', 'opaque-token');
$request = new RetentionExecutionRequest('room_booking_delete', '2.3', '2026-09-29T10:00:00+00:00', 100);
$page = new RetentionExecutionPage([$candidate], ['hold:1'], ['synthetic warning']);
$batch = new RetentionExecutionBatch($request, $page->candidates());
$result = new RetentionExecutionResult(['booking:17'], [], [], []);

if ($policy->action() !== 'DELETE' || $policy->version() !== '2.3' || $policy->durationPeriod() !== 'P1Y') {
    throw new RuntimeException('Der V2-Policyvertrag projiziert die freigegebene DELETE-Policy nicht stabil.');
}
if ($candidate->executionToken() !== 'opaque-token' || $batch->request()->evaluatedAt() !== '2026-09-29T10:00:00+00:00') {
    throw new RuntimeException('Dry-Run-Stand und opaker Ausführungstoken gehen zwischen Plan und Ausführung verloren.');
}
if ($page->heldReferences() !== ['hold:1'] || $result->deletedReferences() !== ['booking:17']) {
    throw new RuntimeException('Hold- und Ergebnisstatus werden nicht datensparsam projiziert.');
}

foreach ([
    static fn() => new RetentionExecutionPolicy('bad id', 'Daten', 'Zweck', 'COMPLETED_AT', 'P1Y', 'DELETE', '2.0'),
    static fn() => new RetentionExecutionPolicy('valid_id', 'Daten', 'Zweck', 'COMPLETED_AT', 'P1Y', 'REVIEW', '2.0'),
    static fn() => new RetentionExecutionCandidate('valid_id', 'ref', '2025-01-01T00:00:00+00:00', 'REVIEW', '2.0', 'token'),
    static fn() => new RetentionExecutionRequest('valid_id', '2.0', 'invalid', 100),
] as $invalid) {
    try {
        $invalid();
        throw new RuntimeException('Ungültige V2-Ausführungsdaten wurden akzeptiert.');
    } catch (InvalidArgumentException) {
    }
}

echo "Retention execution V2 contract tests passed.\n";

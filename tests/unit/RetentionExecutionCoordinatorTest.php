<?php

declare(strict_types=1);

use OCA\FilzmannDataProtection\PublicApi\V2\RegisterRetentionExecutionProvidersEvent;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionCandidate;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionPage;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionPolicy;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionProvider;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionProviderDescriptor;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionRequest;
use OCA\FilzmannDataProtection\PublicApi\V2\RetentionExecutionResult;
use OCA\FilzmannDataProtection\Service\RetentionExecutionCoordinator;
use OCA\FilzmannDataProtection\Service\RetentionExecutionProfileStatus;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

$logger = new class implements LoggerInterface {
    public array $records = [];
    public function emergency(string|\Stringable $message, array $context = []): void { $this->log('emergency', $message, $context); }
    public function alert(string|\Stringable $message, array $context = []): void { $this->log('alert', $message, $context); }
    public function critical(string|\Stringable $message, array $context = []): void { $this->log('critical', $message, $context); }
    public function error(string|\Stringable $message, array $context = []): void { $this->log('error', $message, $context); }
    public function warning(string|\Stringable $message, array $context = []): void { $this->log('warning', $message, $context); }
    public function notice(string|\Stringable $message, array $context = []): void { $this->log('notice', $message, $context); }
    public function info(string|\Stringable $message, array $context = []): void { $this->log('info', $message, $context); }
    public function debug(string|\Stringable $message, array $context = []): void { $this->log('debug', $message, $context); }
    public function log(mixed $level, string|\Stringable $message, array $context = []): void { $this->records[] = [$level, (string)$message, $context]; }
};

$provider = new class implements RetentionExecutionProvider {
    public int $plans = 0;
    public int $executions = 0;
    public function descriptor(): RetentionExecutionProviderDescriptor { return new RetentionExecutionProviderDescriptor('adroom', 'AD Raumplaner', '2.0', 100); }
    public function policies(): array { return [new RetentionExecutionPolicy('room_booking_delete', 'Raumbuchungen', 'Löschung', 'COMPLETED_AT', 'P1Y', 'DELETE', '2.1')]; }
    public function plan(RetentionExecutionRequest $request): RetentionExecutionPage {
        $this->plans++;
        return new RetentionExecutionPage([new RetentionExecutionCandidate($request->policyId(), 'booking:17', '2025-01-01T00:00:00+00:00', 'DELETE', $request->policyVersion(), 'token-17')]);
    }
    public function execute(RetentionExecutionBatch $batch): RetentionExecutionResult {
        $this->executions++;
        return new RetentionExecutionResult(['booking:17'], [], [], []);
    }
};
$events = new class($provider) implements IEventDispatcher {
    public function __construct(private RetentionExecutionProvider $provider) {}
    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterRetentionExecutionProvidersEvent) throw new RuntimeException('Unexpected event.');
        $event->register($this->provider);
        return $event;
    }
};
$profile = new class implements RetentionExecutionProfileStatus {
    public array $status = ['executionAvailable'=>false,'configuration'=>null];
    public function status(): array { return $this->status; }
};
$coordinator = new RetentionExecutionCoordinator($events, $profile, $logger);
$disabled = $coordinator->run('2026-09-29T10:00:00+00:00');
if ($disabled['status'] !== 'blocked' || $provider->plans !== 0 || $provider->executions !== 0) {
    throw new RuntimeException('Ohne explizit gültiges Ausführungsprofil darf kein Provider aufgerufen werden.');
}

$profile->status = [
    'executionAvailable'=>true,
    'configuration'=>['approvedPolicyIds'=>['adroom:room_booking_delete']],
];
$report = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($report['providers']['adroom']['policies']['room_booking_delete']['deleted'] ?? null) !== 1 || $provider->plans !== 1 || $provider->executions !== 1) {
    throw new RuntimeException('Freigegebene V2-Policy wird nicht mit Dry Run und unverändertem Batch ausgeführt.');
}

$profile->status['configuration']['approvedPolicyIds'] = ['adroom:foreign_policy'];
$notApproved = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($notApproved['providers']['adroom']['policies'] ?? null) !== [] || $provider->plans !== 1 || $provider->executions !== 1) {
    throw new RuntimeException('Nicht freigegebene Policy hatte Ausführungsnebenwirkungen.');
}

$failingEvents = new class implements IEventDispatcher {
    public function dispatchTyped(Event $event): Event {
        throw new RuntimeException('sensitive provider detail');
    }
};
$failed = (new RetentionExecutionCoordinator($failingEvents, $profile, $logger))->run('2026-09-29T10:00:00+00:00');
if ($failed !== ['status'=>'failed', 'diagnosticCode'=>'provider_discovery_failed', 'providers'=>[]]) {
    throw new RuntimeException('Provider-Discovery-Fehler wird nicht stabil und datensparsam gemeldet.');
}
$lastLog = $logger->records[array_key_last($logger->records)] ?? null;
if (($lastLog[0] ?? null) !== 'error'
    || ($lastLog[2] ?? null) !== ['stage'=>'provider_discovery', 'error_type'=>RuntimeException::class]
    || str_contains(json_encode($lastLog, JSON_THROW_ON_ERROR), 'sensitive provider detail')) {
    throw new RuntimeException('Provider-Discovery-Fehler wird nicht strukturiert oder nicht datensparsam protokolliert.');
}

echo "Retention execution coordinator tests passed.\n";

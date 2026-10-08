<?php

declare(strict_types=1);

use OCA\FlzDataProtection\PublicApi\V2\RegisterRetentionExecutionProvidersEvent;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionBatch;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionCandidate;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPage;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionPolicy;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionProvider;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionProviderDescriptor;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionRequest;
use OCA\FlzDataProtection\PublicApi\V2\RetentionExecutionResult;
use OCA\FlzDataProtection\Service\RetentionExecutionCoordinator;
use OCA\FlzDataProtection\Service\RetentionExecutionProfileStatus;
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
    public bool $failExecution = false;
    public function descriptor(): RetentionExecutionProviderDescriptor { return new RetentionExecutionProviderDescriptor('flzroom', 'Filzmann Raumplaner', '2.0', 100); }
    public function policies(): array { return [new RetentionExecutionPolicy('room_booking_delete', 'Raumbuchungen', 'Löschung', 'COMPLETED_AT', 'P1Y', 'DELETE', '2.1')]; }
    public function plan(RetentionExecutionRequest $request): RetentionExecutionPage {
        $this->plans++;
        return new RetentionExecutionPage([new RetentionExecutionCandidate($request->policyId(), 'booking:17', '2025-01-01T00:00:00+00:00', 'DELETE', $request->policyVersion(), 'token-17')]);
    }
    public function execute(RetentionExecutionBatch $batch): RetentionExecutionResult {
        $this->executions++;
        if ($this->failExecution) {
            return new RetentionExecutionResult([], [], [], ['booking:17']);
        }
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
    'configuration'=>['approvedPolicyIds'=>[
        'flzroom:room_booking_delete',
        'missing_app:missing_policy',
    ]],
];
$missingCoverage = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($missingCoverage['status'] ?? null) !== 'blocked'
    || ($missingCoverage['diagnosticCode'] ?? null) !== 'provider_coverage_incomplete'
    || $provider->plans !== 0
    || $provider->executions !== 0) {
    throw new RuntimeException('Fehlende genehmigte Provider-Coverage muss vor jeder Ausführung global blockieren.');
}

$profile->status['configuration']['approvedPolicyIds'] = ['flzroom:room_booking_delete'];
$report = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($report['providers']['flzroom']['policies']['room_booking_delete']['deleted'] ?? null) !== 1 || $provider->plans !== 1 || $provider->executions !== 1) {
    throw new RuntimeException('Freigegebene V2-Policy wird nicht mit Dry Run und unverändertem Batch ausgeführt.');
}

$profile->status['configuration']['approvedPolicyIds'] = ['flzroom:foreign_policy'];
$notApproved = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($notApproved['status'] ?? null) !== 'blocked'
    || ($notApproved['diagnosticCode'] ?? null) !== 'provider_coverage_incomplete'
    || $provider->plans !== 1
    || $provider->executions !== 1) {
    throw new RuntimeException('Unbekannte genehmigte Policy muss ohne Ausführungsnebenwirkung blockieren.');
}

$provider->failExecution = true;
$profile->status['configuration']['approvedPolicyIds'] = ['flzroom:room_booking_delete'];
$failedCandidate = $coordinator->run('2026-09-29T10:00:00+00:00');
if (($failedCandidate['status'] ?? null) !== 'failed'
    || ($failedCandidate['diagnosticCode'] ?? null) !== 'provider_execution_failed'
    || ($failedCandidate['providers']['flzroom']['status'] ?? null) !== 'failed'
    || ($failedCandidate['providers']['flzroom']['policies']['room_booking_delete']['failed'] ?? null) !== 1
    || ($failedCandidate['providers']['flzroom']['policies']['room_booking_delete']['deleted'] ?? null) !== 0) {
    throw new RuntimeException('Providerseitig fehlgeschlagene Kandidaten dürfen nicht als erfolgreicher Lauf erscheinen.');
}
$provider->failExecution = false;

$registrationFailureEvents = new class($provider) implements IEventDispatcher {
    public function __construct(private RetentionExecutionProvider $provider) {}
    public function dispatchTyped(Event $event): Event {
        if (!$event instanceof RegisterRetentionExecutionProvidersEvent) throw new RuntimeException('Unexpected event.');
        $event->register($this->provider);
        $event->register(new class implements RetentionExecutionProvider {
            public function descriptor(): RetentionExecutionProviderDescriptor { return new RetentionExecutionProviderDescriptor('broken_app', 'Defekter Provider', '2.0', 100); }
            public function policies(): array { throw new RuntimeException('sensitive registration detail'); }
            public function plan(RetentionExecutionRequest $request): RetentionExecutionPage { throw new RuntimeException('must not plan'); }
            public function execute(RetentionExecutionBatch $batch): RetentionExecutionResult { throw new RuntimeException('must not execute'); }
        });
        return $event;
    }
};
$plansBeforeRegistrationFailure = $provider->plans;
$executionsBeforeRegistrationFailure = $provider->executions;
$registrationFailed = (new RetentionExecutionCoordinator($registrationFailureEvents, $profile, $logger))->run('2026-09-29T10:00:00+00:00');
if (($registrationFailed['status'] ?? null) !== 'failed'
    || ($registrationFailed['diagnosticCode'] ?? null) !== 'provider_execution_failed'
    || ($registrationFailed['providers']['broken_app']['status'] ?? null) !== 'failed'
    || $provider->plans !== $plansBeforeRegistrationFailure
    || $provider->executions !== $executionsBeforeRegistrationFailure
    || str_contains(json_encode($logger->records, JSON_THROW_ON_ERROR), 'sensitive registration detail')) {
    throw new RuntimeException('Registrierungsfehler müssen global vor Ausführung und datensparsam fehlschlagen.');
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

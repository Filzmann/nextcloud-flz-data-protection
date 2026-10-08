<?php

declare(strict_types=1);

use OCA\FlzDataProtection\BackgroundJob\RetentionExecutionJob;
use OCA\FlzDataProtection\Service\RetentionExecutionCoordinator;
use OCA\FlzDataProtection\Service\RetentionExecutionProfileStatus;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

$events = new class implements IEventDispatcher {
    public function dispatchTyped(Event $event): Event { throw new RuntimeException('provider unavailable'); }
};
$profile = new class implements RetentionExecutionProfileStatus {
    public function status(): array {
        return ['executionAvailable'=>true, 'configuration'=>['approvedPolicyIds'=>['flzroom:room_booking_delete']]];
    }
};
$logger = new class implements LoggerInterface {
    public function emergency(string|\Stringable $message, array $context = []): void {}
    public function alert(string|\Stringable $message, array $context = []): void {}
    public function critical(string|\Stringable $message, array $context = []): void {}
    public function error(string|\Stringable $message, array $context = []): void {}
    public function warning(string|\Stringable $message, array $context = []): void {}
    public function notice(string|\Stringable $message, array $context = []): void {}
    public function info(string|\Stringable $message, array $context = []): void {}
    public function debug(string|\Stringable $message, array $context = []): void {}
    public function log(mixed $level, string|\Stringable $message, array $context = []): void {}
};
$clock = new class implements ITimeFactory {
    public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-09-29T10:00:00+00:00'); }
};
$job = new RetentionExecutionJob($clock, new RetentionExecutionCoordinator($events, $profile, $logger));
$run = new ReflectionMethod($job, 'run');
$run->setAccessible(true);

try {
    $run->invoke($job, null);
    throw new RuntimeException('Ein fehlgeschlagener Retention-Lauf wurde vom Background-Job als Erfolg behandelt.');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'Retention execution failed: provider_discovery_failed') {
        throw $error;
    }
}

echo "Retention execution job failure propagation test passed.\n";

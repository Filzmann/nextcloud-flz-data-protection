<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\BackgroundJob;

use OCA\FilzmannDataProtection\Service\RetentionExecutionCoordinator;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use RuntimeException;

final class RetentionExecutionJob extends TimedJob {
    public function __construct(
        ITimeFactory $time,
        private RetentionExecutionCoordinator $coordinator,
    ) {
        parent::__construct($time);
        $this->setInterval(3600);
    }

    protected function run($argument): void {
        $result = $this->coordinator->run();
        if (($result['status'] ?? null) !== 'failed') {
            return;
        }

        $diagnosticCode = $result['diagnosticCode'] ?? 'unknown_failure';
        throw new RuntimeException('Retention execution failed: ' . $diagnosticCode);
    }
}

<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class RetentionPolicy {
    public function __construct(
        private string $policyId,
        private string $dataClass,
        private string $purpose,
        private string $trigger,
        private int $durationDays,
        private string $action,
        private string $version,
    ) {
        if (!preg_match('/^[a-z][a-z0-9-]{1,63}$/', $policyId)) {
            throw new InvalidArgumentException('Invalid retention policy ID.');
        }
        if ($dataClass === '' || $purpose === '') {
            throw new InvalidArgumentException('Retention policy labels must not be empty.');
        }
        if (!in_array($trigger, ['CREATED_AT', 'COMPLETED_AT', 'SUBJECT_EVENT', 'FIXED_DATE', 'NO_AUTO_ACTION'], true)) {
            throw new InvalidArgumentException('Invalid retention trigger.');
        }
        if ($durationDays < 1 || $durationDays > 3650 || $action !== 'REVIEW') {
            throw new InvalidArgumentException('V1 retention preview supports REVIEW periods from 1 to 3650 days.');
        }
        if (!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $version)) {
            throw new InvalidArgumentException('Invalid retention policy version.');
        }
    }

    public function policyId(): string { return $this->policyId; }
    public function action(): string { return $this->action; }
    public function durationDays(): int { return $this->durationDays; }

    public function toArray(): array {
        return [
            'policyId' => $this->policyId,
            'dataClass' => $this->dataClass,
            'purpose' => $this->purpose,
            'trigger' => $this->trigger,
            'durationDays' => $this->durationDays,
            'action' => $this->action,
            'version' => $this->version,
        ];
    }
}

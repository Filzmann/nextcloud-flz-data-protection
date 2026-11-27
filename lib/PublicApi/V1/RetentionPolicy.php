<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class RetentionPolicy {
    public function __construct(
        private string $policyId,
        private string $dataClass,
        private string $purpose,
        private string $trigger,
        private int|string $durationDays,
        private string $action,
        private string $version,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId)) {
            throw new InvalidArgumentException('Ungültige Retention-Richtlinien-ID.');
        }
        if ($dataClass === '' || $purpose === '') {
            throw new InvalidArgumentException('Retention-Richtlinienbezeichnungen dürfen nicht leer sein.');
        }
        if (!in_array($trigger, ['CREATED_AT', 'COMPLETED_AT', 'SUBJECT_EVENT', 'FIXED_DATE', 'NO_AUTO_ACTION'], true)) {
            throw new InvalidArgumentException('Ungültiger Retention-Trigger.');
        }
        if (!$this->validDuration($durationDays) || $action !== 'REVIEW') {
            throw new InvalidArgumentException('V1-Retention-Vorschau unterstützt REVIEW-Zeiträume von bis zu zehn Jahren.');
        }
        if (!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $version)) {
            throw new InvalidArgumentException('Ungültige Retention-Richtlinienversion.');
        }
    }

    public function policyId(): string { return $this->policyId; }
    public function action(): string { return $this->action; }
    public function durationDays(): ?int { return is_int($this->durationDays) ? $this->durationDays : null; }
    public function durationPeriod(): ?string { return is_string($this->durationDays) ? $this->durationDays : null; }

    public function toArray(): array {
        $result = [
            'policyId' => $this->policyId,
            'dataClass' => $this->dataClass,
            'purpose' => $this->purpose,
            'trigger' => $this->trigger,
            'action' => $this->action,
            'version' => $this->version,
        ];
        $result[is_int($this->durationDays) ? 'durationDays' : 'durationPeriod'] = $this->durationDays;
        return $result;
    }

    private function validDuration(int|string $duration): bool {
        if (is_int($duration)) return $duration >= 1 && $duration <= 3650;
        if (!preg_match('/^P([1-9][0-9]*)([YMD])$/', $duration, $matches)) return false;
        $amount = (int)$matches[1];
        return match ($matches[2]) {
            'Y' => $amount <= 10,
            'M' => $amount <= 120,
            'D' => $amount <= 3650,
            default => false,
        };
    }
}

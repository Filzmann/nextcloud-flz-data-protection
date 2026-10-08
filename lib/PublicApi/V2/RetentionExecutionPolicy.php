<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V2;

use InvalidArgumentException;

final class RetentionExecutionPolicy {
    public function __construct(
        private string $policyId,
        private string $dataClass,
        private string $purpose,
        private string $trigger,
        private string $durationPeriod,
        private string $action,
        private string $version,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId) || $dataClass === '' || $purpose === '') {
            throw new InvalidArgumentException('Invalid retention execution policy.');
        }
        if (!in_array($trigger, ['CREATED_AT', 'COMPLETED_AT', 'FIXED_DATE'], true)
            || !preg_match('/^P([1-9][0-9]*)([YMD])$/', $durationPeriod)
            || $action !== 'DELETE'
            || !preg_match('/^2\.[0-9]+$/', $version)) {
            throw new InvalidArgumentException('Invalid retention execution policy semantics.');
        }
    }

    public function policyId(): string { return $this->policyId; }
    public function durationPeriod(): string { return $this->durationPeriod; }
    public function action(): string { return $this->action; }
    public function version(): string { return $this->version; }
    public function toArray(): array {
        return ['policyId'=>$this->policyId,'dataClass'=>$this->dataClass,'purpose'=>$this->purpose,'trigger'=>$this->trigger,'durationPeriod'=>$this->durationPeriod,'action'=>$this->action,'version'=>$this->version];
    }
}

<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V2;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class RetentionExecutionCandidate {
    public function __construct(
        private string $policyId,
        private string $reference,
        private string $occurredAt,
        private string $action,
        private string $policyVersion,
        private string $executionToken,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId)
            || $reference === '' || strlen($reference) > 255
            || $action !== 'DELETE' || !preg_match('/^2\.[0-9]+$/', $policyVersion)
            || $executionToken === '' || strlen($executionToken) > 255) {
            throw new InvalidArgumentException('Invalid retention execution candidate.');
        }
        try { $this->occurredAt = (new DateTimeImmutable($occurredAt))->format(DATE_ATOM); }
        catch (Throwable $error) { throw new InvalidArgumentException('Invalid retention execution date.', 0, $error); }
    }
    public function policyId(): string { return $this->policyId; }
    public function reference(): string { return $this->reference; }
    public function occurredAt(): string { return $this->occurredAt; }
    public function action(): string { return $this->action; }
    public function policyVersion(): string { return $this->policyVersion; }
    public function executionToken(): string { return $this->executionToken; }
    public function toArray(): array { return ['policyId'=>$this->policyId,'reference'=>$this->reference,'occurredAt'=>$this->occurredAt,'action'=>$this->action,'policyVersion'=>$this->policyVersion,'executionToken'=>$this->executionToken]; }
}

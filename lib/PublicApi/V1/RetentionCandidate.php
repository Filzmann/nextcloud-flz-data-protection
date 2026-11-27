<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

use DateTimeImmutable;
use InvalidArgumentException;

final class RetentionCandidate {
    public function __construct(
        private string $policyId,
        private string $reference,
        private string $occurredAt,
        private string $action,
        private string $reviewReason,
        private array $attributes = [],
    ) {
        if (!preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $policyId) || $reference === '' || strlen($reference) > 255) {
            throw new InvalidArgumentException('Ungültige Retention-Kandidatenidentität.');
        }
        try {
            $this->occurredAt = (new DateTimeImmutable($occurredAt))->format(DATE_ATOM);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Ungültiges Retention-Kandidatendatum.');
        }
        if ($action !== 'REVIEW' || $reviewReason === '') {
            throw new InvalidArgumentException('V1-Retention-Kandidaten müssen REVIEW erfordern.');
        }
        foreach ($attributes as $label => $value) {
            if (!is_string($label) || $label === '' || !(is_scalar($value) || $value === null)) {
                throw new InvalidArgumentException('Ungültiges Retention-Kandidatenattribut.');
            }
        }
    }

    public function policyId(): string { return $this->policyId; }
    public function action(): string { return $this->action; }

    public function toArray(): array {
        return [
            'policyId' => $this->policyId,
            'reference' => $this->reference,
            'occurredAt' => $this->occurredAt,
            'action' => $this->action,
            'reviewReason' => $this->reviewReason,
            'attributes' => $this->attributes,
        ];
    }
}

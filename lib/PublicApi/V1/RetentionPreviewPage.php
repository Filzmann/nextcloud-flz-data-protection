<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class RetentionPreviewPage {
    /** @param list<RetentionCandidate> $candidates */
    public function __construct(
        private string $status,
        private array $candidates = [],
        private array $warnings = [],
        private ?string $nextCursor = null,
    ) {
        if (!in_array($status, ['complete', 'partial', 'not_applicable'], true)) {
            throw new InvalidArgumentException('Ungültiger Retention-Vorschaustatus.');
        }
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof RetentionCandidate) throw new InvalidArgumentException('Ungültige Retention-Kandidatenliste.');
        }
        foreach ($warnings as $warning) {
            if (!is_string($warning) || $warning === '') throw new InvalidArgumentException('Ungültige Retention-Warnung.');
        }
        if (($status === 'partial') !== ($nextCursor !== null)) {
            throw new InvalidArgumentException('Teil-Retention-Seiten erfordern genau einen Cursor.');
        }
    }

    public function status(): string { return $this->status; }
    /** @return list<RetentionCandidate> */
    public function candidates(): array { return $this->candidates; }
    public function warnings(): array { return $this->warnings; }
    public function nextCursor(): ?string { return $this->nextCursor; }
}

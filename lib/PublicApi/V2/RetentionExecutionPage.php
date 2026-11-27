<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V2;

use InvalidArgumentException;

final class RetentionExecutionPage {
    /** @param list<RetentionExecutionCandidate> $candidates @param list<string> $heldReferences @param list<string> $warnings */
    public function __construct(private array $candidates = [], private array $heldReferences = [], private array $warnings = []) {
        foreach ($candidates as $candidate) if (!$candidate instanceof RetentionExecutionCandidate) throw new InvalidArgumentException('Ungültige Ausführungskandidatenliste.');
        foreach ([...$heldReferences, ...$warnings] as $value) if (!is_string($value) || $value === '') throw new InvalidArgumentException('Ungültige Ausführungsseiten-Metadaten.');
    }
    public function candidates(): array { return $this->candidates; }
    public function heldReferences(): array { return $this->heldReferences; }
    public function warnings(): array { return $this->warnings; }
}

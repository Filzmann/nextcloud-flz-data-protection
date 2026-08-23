<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Model;

use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataEntry;

final class ProviderReport {
    /**
     * @param list<PersonalDataEntry> $entries
     * @param list<string> $restrictions
     */
    public function __construct(
        private string $status,
        private array $entries,
        private array $restrictions,
        private ?string $nextCursor,
    ) {
    }

    public function status(): string {
        return $this->status;
    }

    /** @return list<PersonalDataEntry> */
    public function entries(): array {
        return $this->entries;
    }

    /** @return list<string> */
    public function restrictions(): array {
        return $this->restrictions;
    }

    public function nextCursor(): ?string {
        return $this->nextCursor;
    }
}

<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class PersonalDataPage {
    private const STATUSES = ['complete', 'partial', 'not_applicable'];

    /**
     * @param list<PersonalDataEntry> $entries
     * @param list<string> $restrictions
     */
    public function __construct(
        private string $status,
        private array $entries = [],
        private array $restrictions = [],
        private ?string $nextCursor = null,
    ) {
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid provider page status.');
        }
        foreach ($entries as $entry) {
            if (!$entry instanceof PersonalDataEntry) {
                throw new InvalidArgumentException('Invalid personal data entry.');
            }
        }
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

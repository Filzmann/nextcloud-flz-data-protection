<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class PersonalDataRequest {
    public function __construct(
        private DataSubjectRef $subject,
        private string $language,
        private string $purpose,
        private int $pageLimit,
        private ?string $cursor,
    ) {
        if ($language === '' || $purpose === '' || $pageLimit < 1 || $pageLimit > 1000) {
            throw new InvalidArgumentException('Invalid personal data request.');
        }
    }

    public function subject(): DataSubjectRef {
        return $this->subject;
    }

    public function language(): string {
        return $this->language;
    }

    public function purpose(): string {
        return $this->purpose;
    }

    public function pageLimit(): int {
        return $this->pageLimit;
    }

    public function cursor(): ?string {
        return $this->cursor;
    }

    public function withPageLimit(int $pageLimit): self {
        return new self($this->subject, $this->language, $this->purpose, $pageLimit, $this->cursor);
    }
}


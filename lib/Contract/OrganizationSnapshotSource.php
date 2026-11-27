<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Contract;

interface OrganizationSnapshotSource {
    /** @return array<string,mixed>|null */
    public function snapshot(): ?array;
}

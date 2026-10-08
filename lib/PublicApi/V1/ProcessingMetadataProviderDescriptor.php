<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\PublicApi\V1;

use InvalidArgumentException;

final class ProcessingMetadataProviderDescriptor {
    public function __construct(
        private string $appId,
        private string $displayName,
        private string $contractVersion,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId)) {
            throw new InvalidArgumentException('Invalid processing metadata provider app ID.');
        }
        if (trim($displayName) === '') {
            throw new InvalidArgumentException('Processing metadata provider display name must not be empty.');
        }
        if (!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $contractVersion)) {
            throw new InvalidArgumentException('Invalid processing metadata provider contract version.');
        }
    }

    public function appId(): string { return $this->appId; }
    public function displayName(): string { return $this->displayName; }
    public function contractVersion(): string { return $this->contractVersion; }
}

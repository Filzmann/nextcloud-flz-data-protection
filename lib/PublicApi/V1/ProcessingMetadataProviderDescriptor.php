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
            throw new InvalidArgumentException('Ungültige Verarbeitungsmetadaten-Provider-App-ID.');
        }
        if (trim($displayName) === '') {
            throw new InvalidArgumentException('Der Anzeigename des Verarbeitungsmetadaten-Providers darf nicht leer sein.');
        }
        if (!preg_match('/^[1-9][0-9]*\.[0-9]+$/', $contractVersion)) {
            throw new InvalidArgumentException('Ungültige Vertragsversion des Verarbeitungsmetadaten-Providers.');
        }
    }

    public function appId(): string { return $this->appId; }
    public function displayName(): string { return $this->displayName; }
    public function contractVersion(): string { return $this->contractVersion; }
}

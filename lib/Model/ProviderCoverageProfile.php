<?php

declare(strict_types=1);

namespace OCA\FlzDataProtection\Model;

use InvalidArgumentException;

final class ProviderCoverageProfile {
    /** @var list<string> */
    private array $expectedAppIds;

    /** @param list<string> $expectedAppIds */
    public function __construct(array $expectedAppIds) {
        if ($expectedAppIds === []) {
            throw new InvalidArgumentException('Ein Abdeckungsprofil erfordert mindestens einen erwarteten Provider.');
        }
        foreach ($expectedAppIds as $appId) {
            if (!is_string($appId) || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId)) {
                throw new InvalidArgumentException('Ungültige erwartete Provider-App-ID.');
            }
        }

        $this->expectedAppIds = array_values(array_unique($expectedAppIds));
        sort($this->expectedAppIds, SORT_STRING);
    }

    /** @return list<string> */
    public function expectedAppIds(): array {
        return $this->expectedAppIds;
    }
}

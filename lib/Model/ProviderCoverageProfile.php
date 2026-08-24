<?php

declare(strict_types=1);

namespace OCA\FilzmannDataProtection\Model;

use InvalidArgumentException;

final class ProviderCoverageProfile {
    /** @var list<string> */
    private array $expectedAppIds;

    /** @param list<string> $expectedAppIds */
    public function __construct(array $expectedAppIds) {
        if ($expectedAppIds === []) {
            throw new InvalidArgumentException('A coverage profile requires at least one expected provider.');
        }
        foreach ($expectedAppIds as $appId) {
            if (!is_string($appId) || !preg_match('/^[a-z][a-z0-9_]{1,63}$/', $appId)) {
                throw new InvalidArgumentException('Invalid expected provider app ID.');
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

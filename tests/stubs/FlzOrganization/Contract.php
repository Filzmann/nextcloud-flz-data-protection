<?php

declare(strict_types=1);

namespace OCA\FlzOrganization\PublicApi\V1;

final class Contract
{
    public static bool $compatible = true;

    public static function supports(string $version): bool
    {
        return self::$compatible && $version === '1.0';
    }
}
